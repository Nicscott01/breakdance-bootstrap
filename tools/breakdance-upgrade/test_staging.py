import hashlib
from pathlib import Path
from tempfile import TemporaryDirectory
from types import SimpleNamespace
import unittest
from unittest.mock import Mock, patch

import staging


class StagingTests(unittest.TestCase):
    def test_origin_requires_an_explicit_loopback_port(self):
        self.assertEqual(staging.loopback_origin("http://127.0.0.1:8802/"), "http://127.0.0.1:8802")
        self.assertEqual(staging.loopback_origin("http://[::1]:8802"), "http://[::1]:8802")
        for origin in (
            "https://example.org", "https://127.0.0.1:8802", "http://127.0.0.1:80",
            "http://localhost:8802", "http://127.0.0.1.example.org:8802", "http://@127.0.0.1:8802",
            "http://127.0.0.1:8802/shop", "http://127.0.0.1:8802/?redirect=example.org",
        ):
            with self.subTest(origin=origin), self.assertRaises(staging.StageError):
                staging.loopback_origin(origin)

    def test_database_mismatch_stops_before_wordpress_boot(self):
        stage = Mock(socket=Path("/private/tmp/clone/mysql.sock"), target={"db": "cloned"},
                     url="http://127.0.0.1:8802")
        stage.wp_json.side_effect = [True, "db.example.org"]
        with self.assertRaisesRegex(staging.StageError, "DB_HOST"):
            staging.Stage.gate(stage)
        stage.info.assert_not_called()

    def test_theme_inventory_loads_plugin_registrations_after_target_checks(self):
        plugins = {"breakdance/plugin.php": "2.8.3"}
        themes = {"breakdance-zero": "1.0.0"}
        stage = Mock()
        stage.info.return_value = themes
        self.assertEqual(staging.Stage.components(stage, {"active_plugins": plugins}),
                         {"plugins": plugins, "themes": themes})
        stage.info.assert_called_once_with("themes", "--skip-themes")

    def test_evidence_filenames_and_routes_stay_in_scope(self):
        for name, path in (
            ("../shop", "/shop/"),
            ("shop", "//example.org/"),
            ("shop", "/\\example.org/"),
        ):
            with self.subTest(name=name, path=path), self.assertRaises(staging.StageError):
                staging.parse_visual({"routes": [{"name": name, "path": path}]})
        with self.assertRaises(staging.StageError):
            staging.parse_visual({"routes": [{"name": "shop", "path": "/", "max_changed_pixels_percent": 120}]})

    def test_warmup_records_initial_response_without_following_redirects(self):
        origin = staging.loopback_origin("http://127.0.0.1:8802")
        visual = staging.parse_visual({"routes": [{"name": "home", "path": "/"}]})

        with TemporaryDirectory() as directory:
            evidence = Path(directory) / "warmup"
            seen = []

            def curl(command, **kwargs):
                self.assertEqual(command[0:2], ["curl", "--disable"])
                self.assertIn("--no-location", command)
                self.assertNotIn("--location", command)
                self.assertEqual(command[-2:], ["--", origin + "/"])
                self.assertEqual(command[command.index("--max-time") + 1], "10")
                seen.append(command[command.index("--output") + 1])
                if len(seen) == 1:
                    Path(seen[-1]).write_text("<p>Redirect response</p>")
                    return SimpleNamespace(stdout="302", returncode=0)
                return SimpleNamespace(stdout="200", returncode=0)

            with patch("staging.subprocess.run", side_effect=curl):
                staging.warm_visual_routes(origin, visual["routes"], evidence)

            first = evidence / "home-first.html"
            self.assertEqual(seen, [str(first), "/dev/null"])
            self.assertEqual(first.read_text(), "<p>Redirect response</p>")
            self.assertEqual(staging.read_json(evidence / "responses.json"), {
                "home": {"first_response": first.name, "requests": [
                    {"http_status": 302, "curl_exit_code": 0},
                    {"http_status": 200, "curl_exit_code": 0},
                ]},
            })

    def test_pixel_geometry_and_font_regressions_are_reported(self):
        selector = ".bde-header-builder"
        before = {
            "pages": {"home": {"mobile": {
                "elements": {selector: {"height": 62.0, "display": "block"}}, "fonts": ["Gilda Display"],
            }}},
        }
        after = {
            "issues": ["home-mobile changed 1.6% of screenshot pixels."],
            "pages": {"home": {"mobile": {
                "elements": {selector: {"height": 63.0, "display": "none"}}, "fonts": [],
            }}},
        }
        changes = staging.compare_visual(before, after, tolerance=0.5)
        self.assertEqual(len(changes), 4)
        for message in ("pixels", "Gilda Display", "62.0 → 63.0", "block → none"):
            self.assertTrue(any(message in change for change in changes), changes)

    def test_rollback_checks_backup_before_replacing_any_files(self):
        with TemporaryDirectory() as directory:
            stage = Mock(run_dir=Path(directory))
            (stage.run_dir / "database.sql.gz").write_bytes(b"changed checkpoint")
            record = {"identity": "fixture", "database_sha256": "original checkpoint"}
            stage.gate.return_value = {"identity": "fixture"}
            with self.assertRaisesRegex(staging.StageError, "database backup does not match"):
                staging.rollback(stage, record)
            stage.wp.assert_not_called()
            self.assertFalse((stage.run_dir / "checkpoint.json").exists())

    def test_plugin_checkpoint_rejects_a_symlink_to_another_tree(self):
        with TemporaryDirectory() as directory:
            plugin = Path(directory) / "plugin"
            plugin.mkdir()
            (plugin / "plugin.php").symlink_to(Path(directory) / "staging-config.php")
            with self.assertRaisesRegex(staging.StageError, "symbolic link"):
                staging.tree_fingerprint(plugin)

    def test_fallback_inventory_compares_identity_independent_of_sql_order(self):
        fallback = [{"id": 2, "content_hash": "a"}, {"id": 8, "content_hash": "b"}]
        before = {"fallbacks": fallback, "design_switches": {}, "icon_sets_hash": "unchanged"}
        after = dict(before, fallbacks=list(reversed(fallback)))
        self.assertEqual(staging.runtime_changed(before, after), [])
        self.assertEqual(staging.runtime_changed(before, dict(after, fallbacks=fallback[:1])), ["fallbacks"])

    def test_maintenance_recovery_is_limited_to_this_checkpoint(self):
        with TemporaryDirectory() as directory:
            root = Path(directory)
            maintenance = root / ".maintenance"
            stage = SimpleNamespace(wp_path=root, run_dir=root / "checkpoint")
            marker = "<?php $upgrading = 1;\n"
            maintenance.write_text(marker)
            with self.assertRaises(staging.StageError), staging.pause_http(stage, recover=True):
                self.fail("A different maintenance marker was overwritten.")
            self.assertEqual(maintenance.read_text(), marker)

            owner = hashlib.sha256(str(stage.run_dir).encode()).hexdigest()
            maintenance.write_text(f"<?php $upgrading = 1; // Creare BD staging {owner}\n")
            with staging.pause_http(stage, recover=True):
                self.assertIn(owner, maintenance.read_text())
            self.assertFalse(maintenance.exists())


if __name__ == "__main__":
    unittest.main()
