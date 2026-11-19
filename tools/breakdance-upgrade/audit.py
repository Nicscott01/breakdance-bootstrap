#!/usr/bin/env python3
"""Read-only Breakdance 2 -> 3 preflight. Never rewrites CSS or WordPress data."""

import argparse
import concurrent.futures
import datetime
from html.parser import HTMLParser
import json
from pathlib import Path
import re
import subprocess
import sys
from urllib.parse import urljoin, urlsplit, urlunsplit

MAX_BYTES = 10 * 1024 * 1024
ANNOUNCEMENT = "https://breakdance.com/meet-breakdance-3-0/"
MIGRATION = "https://breakdance.com/documentation/other/migrate-legacy-selectors/"


def read_text(path):
    if path.stat().st_size > MAX_BYTES:
        raise ValueError(f"Input exceeds 10 MiB: {path.name}")
    return path.read_text(encoding="utf-8", errors="replace")


def public_url(url):
    p = urlsplit(url)
    if p.scheme not in ("http", "https") or not p.hostname or p.username or p.password:
        raise ValueError("Use an HTTP(S) URL without embedded credentials")
    return url


def display_url(url):
    p = urlsplit(url)
    hostname = p.hostname or ""
    host = f"[{hostname}]" if ":" in hostname else hostname
    try:
        port = p.port
    except ValueError:
        return "<invalid-url>"
    netloc = f"{host}:{port}" if port else host
    return urlunsplit((p.scheme, netloc, p.path, "", ""))


def origin(url):
    p = urlsplit(url)
    return p.scheme, p.hostname, p.port or (443 if p.scheme == "https" else 80)


def fetch(url):
    # No redirects: don't turn a same-origin stylesheet into an unreviewed request
    # to another host. No cookies, credentials, or application requests are used.
    p = subprocess.run(
        ["curl", "--disable", "--silent", "--show-error", "--max-time", "30", "--max-filesize",
         str(MAX_BYTES), "--proto", "=http,https", "--write-out", "\n%{http_code}",
         "--", public_url(url)], capture_output=True, text=True, timeout=35,
    )
    body, _, status = p.stdout.rpartition("\n")
    if p.returncode or status != "200":
        raise ValueError(f"HTTP {status or 'failed'} while reading {display_url(url)}")
    return body


class Page(HTMLParser):
    def __init__(self):
        super().__init__()
        self.stylesheets = []
        self.inline = []
        self.in_style = False

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "link" and "stylesheet" in a.get("rel", "").split():
            self.stylesheets.append(a.get("href", ""))
        if tag == "style":
            self.in_style = True
        if a.get("style"):
            self.inline.append(a["style"])

    def handle_endtag(self, tag):
        if tag == "style":
            self.in_style = False

    def handle_data(self, data):
        if self.in_style:
            self.inline.append(data)


def version(path):
    if not path or not (path / "plugin.php").is_file():
        return None
    match = re.search(r"const __BREAKDANCE_VERSION\s*=\s*['\"]([^'\"]+)", read_text(path / "plugin.php"))
    return match[1] if match else None


def css_controls(value, path=(), in_css=False):
    """Read native CSS controls and nested tree exports; ignore editorial/PHP text."""
    if isinstance(value, dict):
        for key, item in value.items():
            yield from css_controls(item, path + (str(key),), in_css or key in ("css", "css_code"))
    elif isinstance(value, list):
        for i, item in enumerate(value):
            yield from css_controls(item, path + (str(i),), in_css)
    elif isinstance(value, str):
        if (not path and value.startswith("{")) or (path and path[-1] == "tree_json_string"):
            yield from css_controls(json.loads(value), path, False)
        elif in_css:
            yield ".".join(path), value


def font_values(value):
    if isinstance(value, dict):
        for item in value.values():
            yield from font_values(item)
    elif isinstance(value, list):
        for item in value:
            yield from font_values(item)
    elif isinstance(value, str):
        if re.fullmatch(r"gfont-[a-z0-9]+", value):
            yield value
        elif value.startswith("{"):
            try:
                yield from font_values(json.loads(value))
            except ValueError:
                pass


def icon_css_controls(value):
    """Keep node context: a placeholder SVG rule alone does not prove an Icon."""
    if isinstance(value, dict):
        data = value.get("data", {})
        if isinstance(data, dict) and data.get("type") == "EssentialElements\\Icon":
            yield from css_controls(data.get("properties", {}))
        for key, item in value.items():
            if key == "tree_json_string" and isinstance(item, str):
                yield from icon_css_controls(json.loads(item))
            else:
                yield from icon_css_controls(item)
    elif isinstance(value, list):
        for item in value:
            yield from icon_css_controls(item)
    elif isinstance(value, str) and value.startswith("{"):
        try:
            yield from icon_css_controls(json.loads(value))
        except ValueError:
            pass


def bundled_fonts(source):
    if not source:
        return None
    path = source / "plugin/fonts/integrations/google-fonts/google-font-list.json"
    if not path.is_file():
        return None
    data = json.loads(read_text(path))
    return {"gfont-" + re.sub(r"[^a-zA-Z0-9]+", "", item["family"]).lower()
            for item in data.get("items", [])}


def audit(project=None, source=None, css=(), builder=(), runtime=None):
    findings = []
    gaps = []
    versions = {"installed_local": None, "composer_lock": None, "reviewed_source": version(source)}
    runtime = {} if runtime is None else runtime
    if not isinstance(runtime, dict):
        raise ValueError("Runtime observations must be a JSON object")
    if any(key in runtime and runtime[key] is not None and not isinstance(runtime[key], str)
           for key in ("breakdance_version", "last_migrated_version", "activation_marker")):
        raise ValueError("Runtime version observations must be strings or null")
    if "font_slugs" in runtime and (not isinstance(runtime["font_slugs"], list)
                                    or any(not isinstance(s, str) for s in runtime["font_slugs"])):
        raise ValueError("font_slugs must be the complete runtime list of string slugs")
    if project and not project.is_dir():
        raise ValueError("Project directory does not exist")
    if source and not version(source):
        raise ValueError("Breakdance source must identify an extracted package version")
    versions["runtime"] = runtime.get("breakdance_version")

    def add(code, owner, evidence, reason, action):
        findings.append(dict(code=code, owner=owner, evidence=evidence, reason=reason,
                             proposed_action=action, auto_apply=False))

    if project:
        for relative in ("web/app/plugins/breakdance", "wp-content/plugins/breakdance"):
            candidate = project / relative
            if version(candidate):
                versions["installed_local"] = version(candidate)
                if not source:
                    source = candidate
                break
        lock = project / "composer.lock"
        if lock.is_file():
            packages = json.loads(read_text(lock)).get("packages", [])
            versions["composer_lock"] = next((p["version"].lstrip("v") for p in packages
                                               if p["name"] == "wenmark/breakdance"), None)
        for relative in ("web/app/plugins/breakdance-bootstrap", "wp-content/plugins/breakdance-bootstrap",
                         "web/app/plugins/breakdance-bootstrap-dev", "wp-content/plugins/breakdance-bootstrap-dev"):
            stylesheet = project / relative / "global-settings/css.twig"
            if stylesheet.is_file() and re.search(r"\.breakdance\s+\.bde-heading", read_text(stylesheet)):
                add("bootstrap-spacing-specificity", "creare-bootstrap", str(stylesheet),
                    "Shared spacing defaults retain more specificity than v3 element controls.",
                    "Update the shared extension's spacing defaults, then verify native per-element overrides on staging.")
        adapters = list((project / "web/app/mu-plugins").glob("*breakdance*compat*.php"))
        if adapters:
            add("existing-runtime-adapter", "site-code", [str(p) for p in adapters],
                "Rendered output may hide defects still present in saved builder data.",
                "Inspect saved controls and verify builder preview/re-save before treating this as a completed content migration.")

    versions["reviewed_source"] = version(source)
    observed_versions = {key: value for key, value in versions.items() if value}
    if not observed_versions:
        gaps.append({"source": "versions", "reason": "No plugin version was inspected; confirm a v2/v3 package or runtime"})
    for key, value in observed_versions.items():
        if not re.match(r"^[23]\.", value):
            add("unsupported-version", "deployment", {key: value},
                "This v2-to-v3 preflight does not establish compatibility for this version.",
                "Review the installed package and extend the version-specific checks before relying on this report.")
    for left, right in (("installed_local", "composer_lock"), ("reviewed_source", "runtime")):
        if versions[left] and versions[right] and versions[left] != versions[right]:
            add("version-mismatch", "deployment", {left: versions[left], right: versions[right]},
                "These copies cannot establish the same runtime behavior.",
                "Review the exact installed package; align an isolated checkout with the lockfile before integration testing.")

    if "last_migrated_version" in runtime:
        marker = runtime["last_migrated_version"]
        if marker is None or (versions["runtime"] and marker != versions["runtime"]):
            add("native-migration-marker", "breakdance", {"observed": marker, "runtime": versions["runtime"]},
                "Native initialization needs investigation; this alone does not explain CSS cascade conflicts.",
                "Check native activation/update state through the installed version's supported workflow; don't invent a marker or run a reset.")
    if "activation_marker" in runtime and runtime["activation_marker"] != "yes":
        add("native-activation-marker", "breakdance", {"observed": runtime["activation_marker"]},
            "Native activation bookkeeping is missing or invalid. Reactivation can reinstall icons/fallbacks and force design switches.",
            "Recover genuine bookkeeping from a verified backup or validate a state-preserving native repair on a clone; do not blindly reactivate.")

    sources = list(css)
    fonts = set()
    for name, tree in builder:
        sources.extend((f"{name}:{path}", text) for path, text in css_controls(tree))
        fonts.update(font_values(tree))
        for path, text in icon_css_controls(tree):
            text = re.sub(r"/\*.*?\*/", "", text, flags=re.S)
            if re.search(r"%%SELECTOR%%\s+svg\s*\{[^{}]*height\s*:\s*auto", text):
                add("svg-specificity-risk", "custom-css", f"{name}:{path}",
                    "A native Icon's saved SVG auto-height rule loses the old wrapper specificity in v3.",
                    "Measure the rendered icon and compare winning declarations; strengthen only the confirmed affected selector.")
    if not any(text.strip() for _, text in sources) and not builder:
        gaps.append({"source": "styles", "reason": "No CSS or builder evidence was collected; styling was not assessed"})
    # This is a preflight heuristic, not a full CSS parser or a computed-style test.
    # Strip comments so code examples and old disabled rules don't count as rules.
    sources = [(name, re.sub(r"/\*.*?\*/", "", text, flags=re.S)) for name, text in sources]
    combined = "\n".join(text for _, text in sources)
    definitions = set(re.findall(r"(--[\w-]+)\s*:", combined))

    for name, text in sources:
        for match in re.finditer(r"([^{}]+)\{([^{}]*)\}", text):
            selector, declarations = match.groups()
            if re.search(r"(?:^|,)\s*\.breakdance\s+a\s*(?:,|$)", selector) and re.search(r"(?:^|;)\s*color\s*:", declarations):
                unresolved = [m.group(1) for m in re.finditer(r"var\(\s*(--[\w-]+)\s*\)", declarations)
                              if m.group(1) not in definitions]
                add("broad-link-color", "custom-css", {"source": name, "selector": ".breakdance a", "unresolved_in_collected_css": unresolved},
                    "This broad link color can outrank v3 button styles. An absent collected variable may also make color invalid.",
                    "Check computed styles, correct undefined variables, and scope the authored rule to its intended component.")
            if re.search(r"\.bde-icon-[\d-]+\s+svg\s*$", selector.strip()) and re.search(r"height\s*:\s*auto", declarations):
                add("svg-specificity-risk", "custom-css", name,
                    "An element-class + SVG rule can lose to Breakdance's default icon selectors after the wrapper is removed.",
                    "Measure the rendered logo and compare winning declarations; strengthen only the confirmed affected selector.")
        if re.search(r"\.(?:home|page-id-\d+|single|archive)%%SELECTOR%%", text) or re.search(r"\.(?:home|page-id-\d+|single|archive)\.bde-[\w-]+", text):
            add("body-selector-concatenation", "custom-css", name,
                "A body-state class is concatenated with an element selector; v3 no longer supplies the missing ancestor boundary.",
                "Verify the intended body ancestor and change the saved CSS through a supported content API, preserving the rest of the document.")
        fonts.update(re.findall(r"font-family\s*:\s*[\"']?(gfont-[a-z0-9]+)", text, flags=re.I))

    available = set(runtime["font_slugs"]) if "font_slugs" in runtime else bundled_fonts(source)
    if available is not None:
        for font in sorted(fonts - available):
            add("unresolved-font-slug", "fonts", {"slug": font, "registry": "runtime" if "font_slugs" in runtime else "bundled-only"},
                "The saved family is not in the inspected registry. Custom registrations may resolve it; this is not proof of a v3 regression.",
                "Check the runtime font registry; map to the intended registered family or add a deliberately scoped alias.")

    unique = {json.dumps(f, sort_keys=True): f for f in findings}
    return {
        "schema_version": 1,
        "generated_at_utc": datetime.datetime.now(datetime.timezone.utc).isoformat(),
        "mode": "read-only-preflight", "target_major": 3, "versions": versions,
        "validated_package_releases": ["2.8.3", "3.0.2"],
        "sources": {"css_inputs": len(css), "builder_exports": len(builder), "runtime_observations": sorted(runtime)},
        "findings": list(unique.values()),
        "coverage_gaps": gaps,
        "native_upgrade_path": {"legacy_selectors": "Preserved; conversion is optional and is not a repair for arbitrary custom CSS.",
                                "documentation": MIGRATION, "release_notes": ANNOUNCEMENT},
        "limits": ["Heuristics require computed-style and visual verification; no finding authorizes automatic rewriting.",
                   "No builder edit/re-save, payment, or responsive interaction tests are performed by this script.",
                   "Only supplied files, same-origin stylesheets, and explicitly supplied runtime observations are inspected."],
    }


def collect(pages, snapshots):
    css, problems, pending = [], [], set()
    for directory in snapshots:
        if not directory.is_dir():
            raise ValueError("Snapshot directory does not exist")
        inspected = 0
        for path in sorted(directory.iterdir()):
            if path.is_file() and path.suffix.lower() == ".css":
                css.append((str(path), read_text(path)))
                inspected += 1
            elif path.is_file() and path.suffix.lower() in (".html", ".htm"):
                page = Page(); page.feed(read_text(path))
                css.append((str(path) + ":inline", "\n".join(page.inline)))
                inspected += 1
        if not inspected:
            problems.append({"source": str(directory), "reason": "Snapshot contains no HTML/CSS files"})
    for url in pages:
        try:
            page = Page(); page.feed(fetch(url))
            css.append((display_url(url) + ":inline", "\n".join(page.inline)))
            for href in page.stylesheets:
                target = urljoin(url, href)
                if origin(target) == origin(url):
                    pending.add(target)
                elif href:
                    problems.append({"source": display_url(target), "reason": "External stylesheet not fetched"})
        except ValueError as error:
            problems.append({"source": display_url(url), "reason": str(error)})

    def stylesheet(url):
        try:
            return display_url(url), fetch(url), None
        except ValueError as error:
            return display_url(url), "", str(error)

    with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
        for name, text, error in pool.map(stylesheet, sorted(pending)):
            if error:
                problems.append({"source": name, "reason": error})
            else:
                css.append((name, text))
    for name, text in css:
        if re.search(r"@import\s", re.sub(r"/\*.*?\*/", "", text, flags=re.S)):
            problems.append({"source": name, "reason": "CSS @import dependencies not followed"})
    return css, list({json.dumps(p, sort_keys=True): p for p in problems}.values())


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--project", type=Path, help="Bedrock or conventional WordPress project (read only)")
    parser.add_argument("--breakdance-source", type=Path, help="Exact extracted package to review without changing vendor files")
    parser.add_argument("--page", action="append", default=[], type=public_url, help="Public canonical URL; repeat for representative pages")
    parser.add_argument("--snapshot", action="append", default=[], type=Path, help="Directory of existing HTML/CSS captures")
    parser.add_argument("--builder-json", action="append", default=[], type=Path, help="Native document export; read locally, never upload")
    parser.add_argument("--runtime-json", type=Path, help="Explicit read-only runtime observations; see README")
    parser.add_argument("--output", type=Path, help="Write report here instead of stdout")
    parser.add_argument("--strict", action="store_true", help="Exit 2 when findings or coverage gaps require review")
    args = parser.parse_args(argv)
    if not any((args.project, args.page, args.snapshot, args.builder_json)):
        parser.error("Supply a project, public page, snapshot directory, or builder export")
    try:
        css, gaps = collect(args.page, args.snapshot)
        builder = [(p.name, json.loads(read_text(p))) for p in args.builder_json]
        runtime = json.loads(read_text(args.runtime_json)) if args.runtime_json else None
        report = audit(args.project, args.breakdance_source, css, builder, runtime)
        report["coverage_gaps"].extend(gaps)
        text = json.dumps(report, indent=2) + "\n"
        if args.output:
            # Never overwrite site source or an input while writing a report.
            with args.output.open("x", encoding="utf-8") as handle:
                handle.write(text)
        else:
            sys.stdout.write(text)
        return 2 if args.strict and (report["findings"] or report["coverage_gaps"]) else 0
    except (OSError, ValueError, subprocess.SubprocessError) as error:
        print(f"Audit failed: {error}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
