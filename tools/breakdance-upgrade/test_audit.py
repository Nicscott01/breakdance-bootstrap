import contextlib
import io
import json
from pathlib import Path
import tempfile
from types import SimpleNamespace
import unittest
from unittest.mock import patch

import audit


class UpgradeAuditTests(unittest.TestCase):
    def test_version_drift_uses_exact_package_not_bootstrap(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            plugin = root / "web/app/plugins/breakdance"
            plugin.mkdir(parents=True)
            (plugin / "plugin.php").write_text("<?php const __BREAKDANCE_VERSION = '2.8.3';")
            (root / "composer.lock").write_text(json.dumps({"packages": [
                {"name": "nicscott01/breakdance-bootstrap", "version": "v1.4.1"},
                {"name": "wenmark/breakdance", "version": "3.0.2"},
            ]}))
            report = audit.audit(project=root, runtime={"breakdance_version": "3.0.2"})
            self.assertEqual(report["versions"]["composer_lock"], "3.0.2")
            self.assertEqual({f["code"] for f in report["findings"]}, {"version-mismatch"})
            self.assertFalse(any(f["auto_apply"] for f in report["findings"]))

    def test_migration_marker_is_not_guessed(self):
        self.assertFalse(audit.audit()["findings"])
        observed = audit.audit(runtime={"breakdance_version": "3.0.2", "last_migrated_version": None})
        self.assertEqual(observed["findings"][0]["code"], "native-migration-marker")
        self.assertFalse(audit.audit(runtime={"breakdance_version": "3.0.2", "last_migrated_version": "3.0.2"})["findings"])
        activation = audit.audit(runtime={"breakdance_version": "3.0.2", "last_migrated_version": "3.0.2", "activation_marker": None})
        self.assertEqual(activation["findings"][0]["code"], "native-activation-marker")
        self.assertFalse(audit.audit(runtime={"breakdance_version": "3.0.2", "last_migrated_version": "3.0.2", "activation_marker": "yes"})["findings"])

    def test_css_controls_preserve_content_and_detect_nested_exports(self):
        properties = {"custom_css": {"css": {"breakpoint_base": ".home%%SELECTOR%% { color:red; }"}},
                      "php_code": "not CSS .home%%SELECTOR%%", "content": "editorial .home%%SELECTOR%%"}
        tree = {"tree_json_string": json.dumps(properties)}
        controls = list(audit.css_controls(tree))
        self.assertEqual(len(controls), 1)
        report = audit.audit(builder=[("header.json", tree)])
        self.assertEqual([f["code"] for f in report["findings"]], ["body-selector-concatenation"])
        self.assertEqual(properties["custom_css"]["css"]["breakpoint_base"], ".home%%SELECTOR%% { color:red; }")
        self.assertEqual(audit.audit(css=[("fixed.css", ".home %%SELECTOR%% {color:white}")])["findings"], [])

    def test_saved_svg_rule_requires_native_icon_context(self):
        properties = {"settings": {"custom_css": {"css": "%%SELECTOR%% svg {height:auto}"}}}
        icon = {"data": {"type": "EssentialElements\\Icon", "properties": properties}}
        report = audit.audit(builder=[("header.json", icon)])
        self.assertEqual([f["code"] for f in report["findings"]], ["svg-specificity-risk"])
        arbitrary = {"data": {"type": "Custom\\Other", "properties": properties}}
        self.assertEqual(audit.audit(builder=[("other.json", arbitrary)])["findings"], [])

    def test_known_css_risks_are_distinct_and_comments_are_ignored(self):
        css = """
        /* .home.bde-header-builder-1-1 { color:red; } */
        .breakdance a { color:var(--typo); }
        .bde-icon-10-12 svg { height:auto; }
        .home.bde-header-builder-10-1 .border { display:none; }
        """
        findings = audit.audit(css=[("header.css", css)])["findings"]
        self.assertEqual({f["code"] for f in findings},
                         {"broad-link-color", "svg-specificity-risk", "body-selector-concatenation"})
        self.assertEqual(findings[0]["evidence"]["unresolved_in_collected_css"], ["--typo"])
        fixed = ".announcement a{color:var(--ink)} :root{--ink:white} .home .bde-header-builder-1-1{} .bde-icon-1-2 .breakdance-icon-atom>svg{height:auto}"
        self.assertEqual(audit.audit(css=[("fixed.css", fixed)])["findings"], [])

    def test_custom_registered_fonts_override_bundled_assumptions(self):
        builder = [("page.json", {"font_family": "gfont-historicfamily"})]
        absent = audit.audit(builder=builder, runtime={"font_slugs": []})
        self.assertEqual(absent["findings"][0]["code"], "unresolved-font-slug")
        registered = audit.audit(builder=builder, runtime={"font_slugs": ["gfont-historicfamily"]})
        self.assertEqual(registered["findings"], [])

    def test_shared_extension_is_reported_as_extension_owned(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            stylesheet = root / "web/app/plugins/breakdance-bootstrap/global-settings/css.twig"
            stylesheet.parent.mkdir(parents=True)
            stylesheet.write_text(".breakdance .bde-heading { margin: {{ setting }}; }")
            findings = audit.audit(project=root)["findings"]
            self.assertEqual(findings[0]["owner"], "creare-bootstrap")
            self.assertEqual(stylesheet.read_text(), ".breakdance .bde-heading { margin: {{ setting }}; }")

    def test_network_collection_only_follows_same_origin_stylesheets(self):
        html = '<link rel="stylesheet" href="/a.css?ver=3"><link rel="stylesheet" href="https://cdn.example.org/other.css"><style>.a{color:red}</style>'
        calls = []

        def fetch(url):
            calls.append(url)
            return html if url == "https://example.org/" else ".b{color:white}"

        with patch.object(audit, "fetch", side_effect=fetch):
            css, gaps = audit.collect(["https://example.org/"], [])
        self.assertEqual(calls, ["https://example.org/", "https://example.org/a.css?ver=3"])
        self.assertEqual(len(css), 2)
        self.assertNotIn("?ver=3", css[1][0])
        self.assertEqual(len(gaps), 1)
        with self.assertRaises(ValueError):
            audit.public_url("https://user:secret@example.org/")

    def test_fetch_disables_curl_configuration_and_refuses_redirects(self):
        with patch.object(audit.subprocess, "run", return_value=SimpleNamespace(returncode=0, stdout=".x{}\n200")) as run:
            self.assertEqual(audit.fetch("https://example.org/a.css"), ".x{}")
            argv = run.call_args.args[0]
            self.assertEqual(argv[:2], ["curl", "--disable"])
            for option in ["--location", "--cookie", "--netrc", "--user", "--header"]:
                self.assertNotIn(option, argv)
        with patch.object(audit.subprocess, "run", return_value=SimpleNamespace(returncode=0, stdout="redirect\n302")):
            with self.assertRaises(ValueError):
                audit.fetch("https://example.org/")

    def test_report_urls_strip_credentials_query_and_fragment(self):
        url = audit.display_url("https://user:secret@cdn.example.org:8443/style.css?key=private#token")
        self.assertEqual(url, "https://cdn.example.org:8443/style.css")

    def test_report_never_overwrites_source_or_existing_output(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            output = root / "existing.json"
            output.write_text("original")
            with contextlib.redirect_stderr(io.StringIO()):
                result = audit.main(["--project", str(root), "--output", str(output)])
            self.assertEqual(result, 1)
            self.assertEqual(output.read_text(), "original")

    def test_empty_or_missing_inputs_cannot_produce_a_strict_pass(self):
        with self.assertRaises(ValueError):
            audit.audit(project=Path("/path/does/not/exist"))
        with tempfile.TemporaryDirectory() as directory:
            with contextlib.redirect_stdout(io.StringIO()) as stdout:
                result = audit.main(["--snapshot", directory, "--strict"])
            self.assertEqual(result, 2)
            self.assertTrue(json.loads(stdout.getvalue())["coverage_gaps"])
            with contextlib.redirect_stdout(io.StringIO()):
                self.assertEqual(audit.main(["--project", directory, "--strict"]), 2)

    def test_unreviewed_major_and_bad_runtime_types_are_explicit(self):
        report = audit.audit(runtime={"breakdance_version": "4.0.0"})
        self.assertEqual(report["findings"][0]["code"], "unsupported-version")
        for invalid in [[], {"font_slugs": "gfont-a"}, {"breakdance_version": 3}]:
            with self.assertRaises(ValueError):
                audit.audit(runtime=invalid)

    def test_imported_css_remains_an_explicit_coverage_gap(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "main.css").write_text('@import url("more.css"); .x{color:red}')
            css, gaps = audit.collect([], [root])
            self.assertEqual(len(css), 1)
            self.assertIn("@import", gaps[0]["reason"])

    def test_strict_exit_reports_review_without_treating_it_as_a_repair(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "header.css").write_text(".home.bde-header-builder-1-1 {color:red}")
            with contextlib.redirect_stdout(io.StringIO()) as stdout:
                result = audit.main(["--snapshot", str(root), "--strict"])
            self.assertEqual(result, 2)
            report = json.loads(stdout.getvalue())
            self.assertEqual(report["mode"], "read-only-preflight")
            self.assertTrue(all(not f["auto_apply"] for f in report["findings"]))


if __name__ == "__main__":
    unittest.main()
