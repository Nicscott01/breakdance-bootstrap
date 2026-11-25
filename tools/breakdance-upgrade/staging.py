#!/usr/bin/env python3
"""Snapshot, upgrade, verify, and roll back an isolated local Breakdance site."""

import argparse
from contextlib import contextmanager
from datetime import datetime, timezone
import fcntl
import gzip
import hashlib
import json
import math
import os
from pathlib import Path
import re
import secrets
import shutil
import subprocess
import sys
import tempfile
import time
from urllib.parse import urlsplit

import audit

TOOL = Path(__file__).resolve().parent
PLUGINS = {"breakdance": "plugin.php", "breakdance-bootstrap": "breakdance-bootstrap.php"}
RENDER_STATE = ("design_switches", "fallbacks", "icon_sets_hash")
CONTENT_STATE = {"document_hash": "saved Breakdance data", "icon_payload_hash": "saved icon data"}


class StageError(Exception):
    pass


def require(condition, message):
    if not condition:
        raise StageError(message)


def read_json(path):
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path, value):
    with tempfile.NamedTemporaryFile(mode="w", encoding="utf-8", dir=path.parent, delete=False) as handle:
        temporary = Path(handle.name)
        try:
            handle.write(json.dumps(value, indent=2) + "\n")
            handle.close()
            temporary.replace(path)
        finally:
            temporary.unlink(missing_ok=True)


def inside(path, directory):
    return path == directory or directory in path.parents


def loopback_origin(value):
    url = urlsplit(value)
    require(
        url.scheme == "http" and url.hostname in ("127.0.0.1", "::1")
        and url.port is not None and url.port not in (0, 80)
        and url.username is None and url.password is None and not (url.query or url.fragment)
        and not any(char.isspace() for char in value)
        and url.path in ("", "/"),
        "Use a literal loopback HTTP origin with a port, such as http://127.0.0.1:8797.",
    )
    host = "[::1]" if url.hostname == "::1" else "127.0.0.1"
    return f"http://{host}:{url.port}"


def parse_visual(spec):
    require(isinstance(spec, dict), "Visual configuration must be an object.")
    routes = spec.get("routes", [])
    viewports = spec.get("viewports", {"desktop": [1280, 800], "mobile": [390, 844]})
    require(isinstance(routes, list) and isinstance(viewports, dict), "Visual routes and viewports have invalid shapes.")
    require({"desktop", "mobile"} <= viewports.keys(), "Configure desktop and mobile viewports.")
    for label, size in viewports.items():
        require(re.fullmatch(r"[a-zA-Z0-9][a-zA-Z0-9_-]*", label)
                and isinstance(size, list) and len(size) == 2
                and all(type(value) is int and 1 <= value <= 4096 for value in size),
                "Viewport names and sizes must be safe for screenshot capture.")
    names = set()
    for route in routes:
        name, path = route["name"], route["path"]
        require(re.fullmatch(r"[a-zA-Z0-9][a-zA-Z0-9_-]*", name) and name not in names,
                "Visual route names must be unique and filesystem-safe.")
        names.add(name)
        parts = urlsplit(path)
        require(path.startswith("/") and not path.startswith("//") and not parts.netloc
                and not parts.scheme and "\\" not in path and not parts.fragment
                and not any(char.isspace() for char in path),
                f"Visual route {name} must be a path on the staging origin.")
        require(route.get("status", 200) in (200, 404), f"Unsupported expected status for {name}.")
        checks = route.get("checks", {"body": ["font-family"]})
        require(isinstance(checks, dict) and checks
                and all(isinstance(props, list) and props and all(isinstance(prop, str) and prop for prop in props)
                        for props in checks.values()),
                f"Provide selector-to-property lists in the visual checks for {name}.")
        for key in ("fonts", "required_assets"):
            values = route.get(key, [])
            require(isinstance(values, list) and all(isinstance(value, str) and value for value in values),
                    f"{key} must be a list of strings for {name}.")
        if "mobile_menu" in route:
            menu = route["mobile_menu"]
            require(isinstance(menu, dict) and all(isinstance(menu.get(key), str) and menu[key]
                                                  for key in ("button", "open")),
                    f"Provide button and open-menu selectors for {name}.")
        for key, maximum in (("max_changed_pixels_percent", 100), ("max_overflow_px", 4096)):
            if key in route:
                limit = route[key]
                require(type(limit) in (int, float) and 0 <= limit <= maximum,
                        f"{key} must be a number from 0 to {maximum} for {name}.")
    require(routes, "Configure at least one visual route.")
    tolerance = float(spec.get("geometry_tolerance_px", 0.5))
    require(math.isfinite(tolerance) and tolerance >= 0, "Geometry tolerance must be finite and non-negative.")
    return spec


def fingerprint(path):
    with path.open("rb") as handle:
        digest = hashlib.sha256()
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
        return digest.hexdigest()


def tree_fingerprint(root):
    require(root.is_dir() and not root.is_symlink(), f"Missing or linked plugin tree: {root}")
    digest = hashlib.sha256()
    for path in sorted(root.rglob("*")):
        require(not path.is_symlink(), f"Plugin tree contains a symbolic link: {path.relative_to(root)}")
        if path.is_dir():
            continue
        require(path.is_file(), f"Unexpected file type in plugin tree: {path.relative_to(root)}")
        digest.update(path.relative_to(root).as_posix().encode() + b"\0")
        digest.update(fingerprint(path).encode())
        digest.update(f"{path.stat().st_mode & 0o777:o}\n".encode())
    return digest.hexdigest()


def package_version(slug, path):
    if slug == "breakdance":
        version = audit.version(path)
    else:
        text = (path / PLUGINS[slug]).read_text(encoding="utf-8")
        match = re.search(r"^\s*\*?\s*Version:\s*([^\s]+)", text, re.M)
        version = match[1] if match else None
    require(version, f"Cannot determine the version of {slug} in {path}.")
    return version


def warm_visual_routes(origin, routes, directory):
    directory.mkdir(mode=0o700)
    evidence = {}
    for route in routes:
        first_response = directory / f"{route['name']}-first.html"
        responses = []
        # Prime the template and page caches before taking a separate browser capture.
        for output in (first_response, os.devnull):
            response = subprocess.run(
                ["curl", "--disable", "--noproxy", "*", "--globoff", "--no-location",
                 "--proto", "=http", "--silent", "--max-time", "10", "--max-filesize", "10485760",
                 "--write-out", "%{http_code}", "--output", str(output), "--", origin + route["path"]],
                stdout=subprocess.PIPE, text=True, cwd="/",
            )
            responses.append({"http_status": int(response.stdout or 0) or None,
                              "curl_exit_code": response.returncode})
        evidence[route["name"]] = {
            "first_response": first_response.name if first_response.is_file() else None,
            "requests": responses,
        }
    write_json(directory / "responses.json", evidence)


class Stage:
    def __init__(self, config_path, run_dir):
        self.config = config = read_json(config_path)
        self.config_dir = config_path.parent
        self.root = (config_path.parent / config["stage_root"]).resolve(strict=True)
        self.wp_path = (self.root / config["wordpress"]).resolve(strict=True)
        self.socket = (self.root / config["db_socket"]).resolve(strict=True)
        require(self.root.is_dir() and self.root.stat().st_uid == os.getuid(),
                "The staging root must be a local directory owned by the current user.")
        require(inside(self.wp_path, self.root) and inside(self.socket, self.root),
                "WordPress and the staging database socket must reside inside the stage root.")
        require(self.socket.is_socket() and self.socket.stat().st_uid == os.getuid(),
                "The staging database socket must be a local socket owned by the current user.")
        require((self.wp_path / "wp-load.php").is_file(), "WordPress core was not found.")
        require(re.fullmatch(r"[A-Za-z0-9_]+", config["db_name"]), "Invalid staging database name.")
        self.url = loopback_origin(config["url"])
        self.run_dir = run_dir.resolve()
        require(not inside(self.run_dir, self.root) and not inside(self.root, self.run_dir),
                "Keep the run directory outside the staging site and its ancestors.")
        self.visual = parse_visual(config["visual"])
        self.env = dict(os.environ, WP_CLI_CONFIG_PATH=os.devnull)
        self.target = {
            "root": str(self.root),
            "wordpress": str(self.wp_path),
            "socket": str(self.socket),
            "db": config["db_name"],
            "origin": self.url,
        }
        self.env["CREARE_BD_STAGE_TARGET"] = json.dumps(self.target)

    def wp(self, *args, timeout=120, **kwargs):
        command = ["wp", "--skip-packages", "--no-color", "--path=" + str(self.wp_path),
                   "--url=" + self.url, "--user=" + str(self.config["admin"]), *args]
        return subprocess.run(command, env=self.env, cwd="/", check=True, timeout=timeout, **kwargs)

    def wp_json(self, *args):
        result = self.wp(*args, stdout=subprocess.PIPE, text=True)
        return json.loads(result.stdout)

    def gate(self):
        # These literal values are read before booting WordPress or its MU plugins.
        expected = {
            "CREARE_BD_UPGRADE_STAGING": True,
            "DB_HOST": "localhost:" + str(self.socket),
            "DB_NAME": self.target["db"],
            "WP_HOME": self.url,
            "DISABLE_WP_CRON": True,
            "WP_HTTP_BLOCK_EXTERNAL": True,
        }
        for key, value in expected.items():
            actual = self.wp_json("config", "get", key, "--type=constant", "--format=json")
            require(actual == value, f"The literal wp-config.php constant {key} failed the staging gate.")
        env_type = self.wp_json("config", "get", "WP_ENVIRONMENT_TYPE", "--type=constant", "--format=json")
        require(env_type in ("local", "staging"), "WordPress must identify as local or staging.")
        result = self.wp("db", "query", "SELECT @@socket, @@datadir, @@skip_networking;", "--skip-column-names",
                         stdout=subprocess.PIPE, text=True)
        socket, datadir, networking_disabled = result.stdout.strip().split("\t")
        require(networking_disabled in ("1", "ON") and Path(socket).resolve() == self.socket
                and Path(datadir).is_dir() and inside(Path(datadir).resolve(), self.root),
                "The database service must use the staging socket and a staging data directory with networking disabled.")
        return self.info("target", "--skip-plugins", "--skip-themes")

    def info(self, action, *flags):
        return self.wp_json(*flags, "eval-file", str(TOOL / "staging-wordpress.php"), action)

    def components(self, target):
        # Breakdance registers its bundled Zero Theme when the plugin loads.
        return {"plugins": target["active_plugins"], "themes": self.info("themes", "--skip-themes")}

    def observations(self):
        return self.wp_json("eval-file", str(TOOL / "wordpress-observations.php"))

    def package_inputs(self):
        packages = {}
        for slug, name in (("breakdance", "breakdance_package"), ("breakdance-bootstrap", "bootstrap_package")):
            item = self.config.get(name)
            if item:
                packages[slug] = (self.config_dir / item).resolve(strict=True)
        require("breakdance" in packages, "Configure the extracted target Breakdance package.")
        for slug, path in packages.items():
            require(not inside(path, self.root) and not inside(path, self.run_dir),
                    "Keep upgrade packages outside the staging site and run directory.")
            require((path / PLUGINS[slug]).is_file(), f"Missing {slug} package entry point.")
        require(package_version("breakdance", packages["breakdance"]).startswith("3."),
                "The target Breakdance release must be v3.")
        return packages

    def capture(self, directory, site_url, baseline=None, *, warmup=False):
        # Prove the HTTP listener serves this exact WordPress directory before rendering.
        token = secrets.token_urlsafe(24).encode()
        with tempfile.NamedTemporaryFile(dir=self.wp_path, prefix="creare-stage-", suffix=".txt") as sentinel:
            sentinel.write(token)
            sentinel.flush()
            # The local HTTP worker can run with a different staging UID.
            os.chmod(sentinel.name, 0o644)
            url = site_url.rstrip("/") + "/" + Path(sentinel.name).name
            found = subprocess.run(
                ["curl", "--disable", "--noproxy", "*", "--fail", "--silent", "--max-time", "10",
                 "--max-filesize", "1024", "--", url], stdout=subprocess.PIPE, timeout=12, cwd="/",
            )
            require(found.returncode == 0 and found.stdout == token,
                    "The loopback HTTP server is not serving the verified WordPress staging directory.")
        if warmup:
            warm_visual_routes(self.url, self.visual["routes"], directory / "warmup")
        config = directory / "visual-config.json"
        write_json(config, self.visual)
        command = ["node", str(TOOL / "visual-check.mjs"), self.url, str(config), str(directory)]
        if baseline:
            command.append(str(baseline))
        subprocess.run(
            command, check=True, timeout=900, cwd="/", env=self.env,
        )
        return read_json(directory / "visual.json")


@contextmanager
def lock(stage):
    path = stage.root / ".creare-bd-upgrade.lock"
    fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_NOFOLLOW, 0o600)
    with os.fdopen(fd, "w") as handle:
        try:
            fcntl.flock(handle, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError as error:
            raise StageError("Another upgrade operation holds the staging lock.") from error
        yield


@contextmanager
def pause_http(stage, recover=False):
    path = stage.wp_path / ".maintenance"
    owner = hashlib.sha256(os.fsencode(stage.run_dir)).hexdigest()
    marker = f"<?php $upgrading = {int(time.time())}; // Creare BD staging {owner}\n"
    if recover and not path.is_symlink() and path.is_file():
        previous = path.read_text(encoding="utf-8")
        if re.fullmatch(r"<\?php \$upgrading = \d+; // Creare BD staging " + owner + "\n", previous):
            path.unlink()
    try:
        with path.open("x", encoding="utf-8") as handle:
            handle.write(marker)
    except FileExistsError as error:
        raise StageError("The stage is already in maintenance mode. Resolve it before continuing.") from error
    try:
        yield
    finally:
        if not path.is_symlink() and path.is_file() and path.read_text(encoding="utf-8") == marker:
            path.unlink()


def check_source(config):
    source = config["snapshot_source"]
    for key in ("database_at", "files_at"):
        value = datetime.fromisoformat(source[key].replace("Z", "+00:00"))
        require(value.utcoffset() is not None, f"Include a time-zone offset in snapshot_source.{key}.")
    return source


def observed_version(runtime, version):
    require(runtime.get("breakdance_version") == version
            and runtime.get("last_migrated_version") == version and runtime.get("activation_marker") == "yes",
            f"Breakdance {version} and the native initialization markers did not agree.")


def content_changed(before, after):
    return [label for key, label in CONTENT_STATE.items() if before[key] != after[key]]


def runtime_changed(before, after):
    # Templates with the same post date can be returned in a different SQL order.
    def normalized(runtime, key):
        if key == "fallbacks":
            return sorted(runtime[key], key=lambda item: item["id"])
        return runtime[key]

    return [key for key in RENDER_STATE if normalized(before, key) != normalized(after, key)]


def db_snapshot(stage, destination):
    with tempfile.TemporaryFile() as errors:
        process = subprocess.Popen(
            ["wp", "--skip-packages", "--no-color", "--path=" + str(stage.wp_path),
             "--url=" + stage.url, "db", "export", "-", "--quiet", "--single-transaction",
             "--skip-lock-tables", "--skip-add-locks", "--add-drop-table"],
            env=stage.env, cwd="/", stdout=subprocess.PIPE, stderr=errors,
        )
        try:
            with process.stdout as stream, gzip.open(destination, "wb", compresslevel=1) as output:
                shutil.copyfileobj(stream, output, 1024 * 1024)
            if process.wait():
                errors.seek(0)
                (stage.run_dir / "database-export-error.log").write_bytes(errors.read())
                raise StageError("Database export failed. See database-export-error.log in the private run.")
        finally:
            if process.poll() is None:
                process.kill()
                process.wait()
    with gzip.open(destination, "rb") as backup:
        require(b"CREATE TABLE " in backup.read(1024 * 1024), "The database backup is missing table definitions.")


def target_matches(stage, record):
    current = stage.gate()
    require(current["identity"] == record["identity"], "This run was captured from a different staging target.")
    return current


def snapshot(stage):
    target = stage.gate()
    packages = stage.package_inputs()
    source = check_source(stage.config)
    plugins = Path(target["identity"]["plugins"])
    versions = {slug: package_version(slug, plugins / slug) for slug in packages}
    require(versions["breakdance"].startswith("2."), "A new snapshot must start on Breakdance v2.")
    runtime = stage.observations()
    observed_version(runtime, versions["breakdance"])
    components = stage.components(target)
    require(all(version is not None for group in components.values() for version in group.values()),
            "An active plugin or theme is missing from the staging files.")
    if "breakdance-bootstrap" in versions:
        require(runtime.get("bootstrap_version") == versions["breakdance-bootstrap"], "Bootstrap must be active.")
    stage.run_dir.mkdir(mode=0o700)
    content = stage.info("content")
    write_json(stage.run_dir / "snapshot-content.json", content)
    require(not content["media"]["missing_count"], "Staging is missing registered media. See snapshot-content.json.")
    baseline_dir = stage.run_dir / "before"
    baseline_dir.mkdir()
    captured = stage.capture(baseline_dir, target["identity"]["site_url"])
    require(not captured["issues"], "The v2 visual baseline has failures. See before/visual.json.")
    save_audit(target, baseline_dir, runtime)
    originals = stage.run_dir / "original-plugins"
    originals.mkdir()
    trees = {}
    for slug in packages:
        trees[slug] = tree_fingerprint(plugins / slug)
        shutil.copytree(plugins / slug, originals / slug)
        require(tree_fingerprint(originals / slug) == trees[slug], f"{slug} changed during its backup.")
    database = stage.run_dir / "database.sql.gz"
    with pause_http(stage):
        target = stage.info("target", "--skip-plugins", "--skip-themes")
        require(stage.components(target) == components, "Staging plugins or themes changed during the baseline.")
        require(not content_changed(content, stage.info("content")),
                "Saved Breakdance data changed during the baseline. Pause other editors and start a new run.")
        db_snapshot(stage, database)
    record = {"state": "snapshotted", "source": source, "captured_at": datetime.now(timezone.utc).isoformat(),
              "identity": target["identity"], "tables": target["tables"], "components": components,
              "versions": versions, "trees": trees, "database_sha256": fingerprint(database),
              "runtime": runtime, "content": content, "visual_config": stage.visual}
    write_json(stage.run_dir / "checkpoint.json", record)
    print(f"Saved the staging checkpoint in {stage.run_dir}")


def validate_checkpoint(stage, record):
    require(fingerprint(stage.run_dir / "database.sql.gz") == record["database_sha256"],
            "The rollback database backup does not match its checkpoint.")
    for slug, digest in record["trees"].items():
        require(slug in PLUGINS and tree_fingerprint(stage.run_dir / "original-plugins" / slug) == digest,
                f"The saved {slug} directory does not match its checkpoint.")


def replace_tree(destination, source):
    require(not destination.is_symlink(), f"Refusing to swap a linked plugin directory: {destination}")
    with tempfile.TemporaryDirectory(prefix=".creare-bd-", dir=destination.parent) as temporary:
        new = Path(temporary) / "next"
        old = Path(temporary) / "previous"
        shutil.copytree(source, new)
        if destination.exists():
            destination.rename(old)
        try:
            new.rename(destination)
        except OSError:
            if old.exists():
                old.rename(destination)
            raise


def compare_visual(before, after, tolerance):
    changes = list(after["issues"])
    for route, baseline_views in before["pages"].items():
        for viewport, baseline in baseline_views.items():
            current = after["pages"].get(route, {}).get(viewport)
            label = f"{route}/{viewport}"
            if current is None:
                changes.append(f"{label} is missing.")
                continue
            missing_fonts = sorted(set(baseline["fonts"]) - set(current["fonts"]))
            for font in missing_fonts:
                changes.append(f"{label} no longer loaded {font}.")
            for selector, old in baseline["elements"].items():
                new = current["elements"].get(selector)
                if new is None:
                    changes.append(f"{label} {selector} is missing.")
                    continue
                for prop, value in old.items():
                    other = new.get(prop)
                    equal = (abs(value - other) <= tolerance if type(value) in (int, float)
                             and type(other) in (int, float) else value == other)
                    if not equal:
                        changes.append(f"{label} {selector} {prop}: {value} → {other}")
    return changes


def save_audit(target, directory, runtime):
    plugins = Path(target["identity"]["plugins"])
    project = None
    if plugins.parent.name == "wp-content":
        project = plugins.parent.parent
    elif plugins.parent.name == "app" and plugins.parent.parent.name == "web":
        project = plugins.parent.parent.parent
    css, gaps = audit.collect([], [directory / "audit-inputs"])
    report = audit.audit(project=project, source=plugins / "breakdance", css=css, runtime=runtime)
    report["coverage_gaps"].extend(gaps)
    write_json(directory / "audit.json", report)


def verify(stage, record, rollback=False, *, warmup=False):
    target = target_matches(stage, record)
    group = "rollback-" if rollback else "verify-"
    directory = Path(tempfile.mkdtemp(prefix=group, dir=stage.run_dir))
    runtime = stage.observations()
    expected = record["versions"] if rollback else record["target_versions"]
    observed_version(runtime, expected["breakdance"])
    if "breakdance-bootstrap" in expected:
        require(runtime.get("bootstrap_version") == expected["breakdance-bootstrap"],
                "Bootstrap runtime does not match this phase.")
    content = stage.info("content")
    baseline = stage.run_dir / "before"
    actual = stage.capture(directory, target["identity"]["site_url"], baseline, warmup=warmup)
    before = read_json(baseline / "visual.json")
    changes = compare_visual(before, actual, float(stage.visual.get("geometry_tolerance_px", 0.5)))
    if content["media"]["missing_count"]:
        changes.append("Some registered staging media are missing.")
    components = record["components"]
    expected_plugins = dict(components["plugins"])
    for slug, version in expected.items():
        expected_plugins[f"{slug}/{PLUGINS[slug]}"] = version
    if stage.components(target) != {"plugins": expected_plugins, "themes": components["themes"]}:
        changes.append("Active plugin or theme versions differ from the versions expected for this phase.")
    if not rollback:
        native_changes = set(record.get("native_changes", []))
        native_changes.update(runtime_changed(record["runtime"], runtime))
        if content["icon_payload_hash"] != record["content"]["icon_payload_hash"]:
            native_changes.add(CONTENT_STATE["icon_payload_hash"])
        changes.extend(f"Review the change to {item} in the native reports." for item in sorted(native_changes))
    save_audit(target, directory, runtime)
    write_json(directory / "verification.json", {"review_items": changes, "runtime": runtime, "content": content})
    record["last_verification"] = directory.name
    record["state"] = "rolled-back" if rollback else ("review" if changes else "verified")
    write_json(stage.run_dir / "checkpoint.json", record)
    if changes:
        print(f"{len(changes)} checks need review. See {directory / 'verification.json'}")
        return 2
    print(f"Visual and asset checks passed. Evidence: {directory}")
    return 0


def rollback(stage, record):
    target = target_matches(stage, record)
    validate_checkpoint(stage, record)
    extra = sorted(set(target["tables"]) - set(record["tables"]))
    require(all(re.fullmatch(r"[A-Za-z0-9_]+", table) and table.startswith(target["identity"]["prefix"])
                for table in extra), "Unexpected tables outside the staging WordPress prefix. Rollback refused.")
    record["state"] = "rolling-back"
    write_json(stage.run_dir / "checkpoint.json", record)
    with pause_http(stage, recover=True):
        plugins = Path(target["identity"]["plugins"])
        for slug in record["trees"]:
            replace_tree(plugins / slug, stage.run_dir / "original-plugins" / slug)
        with gzip.open(stage.run_dir / "database.sql.gz", "rb") as backup:
            # Pass decompressed SQL to WP-CLI, never through a shell or a live SQL connection.
            with tempfile.TemporaryFile() as sql:
                shutil.copyfileobj(backup, sql, 1024 * 1024)
                sql.seek(0)
                stage.wp("db", "import", "-", "--quiet", stdin=sql, timeout=1200)
        if extra:
            quoted = ", ".join(chr(96) + table + chr(96) for table in extra)
            stage.wp("db", "query", "SET FOREIGN_KEY_CHECKS=0; DROP TABLE IF EXISTS " + quoted)
    current = target_matches(stage, record)
    require(current["tables"] == record["tables"], "The restored database table inventory differs from the checkpoint.")
    restored = stage.info("content")
    require(not content_changed(record["content"], restored), "Restored Breakdance data differs from the checkpoint.")
    runtime = stage.observations()
    changes = runtime_changed(record["runtime"], runtime)
    write_json(stage.run_dir / "native-rollback.json", {"runtime": runtime, "content": restored, "changes": changes})
    require(not changes, "The restored native settings differ from the checkpoint. See native-rollback.json.")
    stage.info("clear-css")
    return verify(stage, record, rollback=True, warmup=True)


def upgrade(stage, record):
    target = target_matches(stage, record)
    require(record["state"] == "snapshotted", "Use a new checkpoint for another upgrade attempt.")
    validate_checkpoint(stage, record)
    packages = stage.package_inputs()
    require(packages.keys() == record["trees"].keys(), "The package selection differs from the checkpoint.")
    require(stage.components(target) == record["components"],
            "Active plugins or themes changed after the checkpoint. Capture a new baseline.")
    plugins = Path(target["identity"]["plugins"])
    require(all(tree_fingerprint(plugins / slug) == digest for slug, digest in record["trees"].items()),
            "Installed plugins changed after the checkpoint. Capture a new baseline.")
    require(not content_changed(record["content"], stage.info("content")),
            "Saved Breakdance data changed after the checkpoint. Capture a new baseline.")
    record["target_versions"] = {slug: package_version(slug, path) for slug, path in packages.items()}
    record["packages"] = {slug: tree_fingerprint(path) for slug, path in packages.items()}
    require(not (stage.wp_path / ".maintenance").exists(), "Resolve staging maintenance mode before upgrading.")
    record["state"] = "upgrading"
    write_json(stage.run_dir / "checkpoint.json", record)
    try:
        with pause_http(stage):
            for slug, source in packages.items():
                replace_tree(plugins / slug, source)
                require(tree_fingerprint(plugins / slug) == record["packages"][slug],
                        f"The installed {slug} package changed during the upgrade.")
        runtime = stage.observations()
        observed_version(runtime, record["target_versions"]["breakdance"])
        if "breakdance-bootstrap" in packages:
            require(runtime.get("bootstrap_version") == record["target_versions"]["breakdance-bootstrap"],
                    "The target Bootstrap release is not active.")
        current = stage.info("content")
        changes = runtime_changed(record["runtime"], runtime)
        changes.extend(content_changed(record["content"], current))
        write_json(stage.run_dir / "native-upgrade.json", {"runtime": runtime, "content": current, "changes": changes})
        stage.info("clear-css")
    except (StageError, OSError, ValueError, subprocess.SubprocessError):
        print("The upgrade could not complete. Restoring the staging checkpoint.", file=sys.stderr)
        rollback(stage, record)
        raise
    record["state"] = "upgraded"
    record["native_changes"] = changes
    write_json(stage.run_dir / "checkpoint.json", record)
    return verify(stage, record, warmup=True)


def main(argv=None):
    os.umask(0o077)
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--config", type=Path, required=True, help="Local staging and visual checks configuration")
    parser.add_argument("--run-dir", type=Path, required=True, help="Private checkpoint and results directory")
    commands = parser.add_subparsers(dest="action", required=True)
    for name in ("snapshot", "upgrade", "verify"):
        commands.add_parser(name)
    commands.add_parser("rollback").add_argument("--yes", action="store_true",
        help="Replace only this staging database and its backed-up plugins with the checkpoint")
    args = parser.parse_args(argv)
    try:
        require(args.action != "rollback" or args.yes, "Rollback requires --yes.")
        stage = Stage(args.config.resolve(strict=True), args.run_dir)
        stage.gate()
        with lock(stage):
            if args.action == "snapshot":
                snapshot(stage)
                return 0
            require(stage.run_dir.stat().st_uid == os.getuid() and not stage.run_dir.stat().st_mode & 0o077,
                    "The existing checkpoint must be private to the current user.")
            record = read_json(stage.run_dir / "checkpoint.json")
            require(stage.visual == record["visual_config"], "Use the same visual checks as the baseline.")
            if args.action == "upgrade":
                return upgrade(stage, record)
            if args.action == "verify":
                require(record["state"] in ("upgraded", "review", "verified"),
                        "Run an upgrade before verifying it.")
                return verify(stage, record)
            require(record["state"] not in ("snapshotted", "rolled-back"),
                    "Rollback requires an applied or interrupted staging upgrade.")
            return rollback(stage, record)
    except (StageError, OSError, ValueError, KeyError, TypeError, subprocess.SubprocessError) as error:
        print(f"Staging workflow failed: {error}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
