# Breakdance 2 to 3 preflight

This portable Python script checks a WordPress/Bedrock checkout, public pages,
existing HTML/CSS snapshots, and optional native document exports. It reports
evidence and proposed actions. It never updates plugins, saves WordPress data,
converts selectors, removes links, changes payments, or rewrites stylesheets.

Requirements: Python 3.9+; `curl` for public-page checks. There are no Python
dependencies. Copy this directory to another project to use it there.

```sh
python3 tools/breakdance-upgrade/audit.py \
  --project /path/to/bedrock \
  --page https://example.org/ \
  --page https://example.org/shop/ \
  --output /tmp/breakdance-preflight.json
```

Use canonical public URLs. Redirects and external-origin stylesheets are reported
as coverage gaps instead of being followed. Queries are omitted from report URLs.
CSS `@import` dependencies are also reported as gaps. No cookies or credentials
are used; automatic curl configuration is disabled. The output path must not already exist.

For an offline review, add `--snapshot /path/to/captured-html-and-css` instead of
`--page`. Only immediate `.html`, `.htm`, and `.css` files in that directory are
read. JSON captures, log files, cookies, and unrelated files are ignored.

`--breakdance-source /path/to/exact/extracted/breakdance` lets the review use the
intended package without replacing the checkout's installed files. The report
compares its version with explicitly supplied runtime observations. It also
compares installed local files with the exact `wenmark/breakdance` lock entry.
It does not assume that `composer.lock` proves which code is installed.

`--builder-json /path/to/native-document-export.json` can be repeated. The script
reads native `css`/`css_code` controls, responsive variants, nested
`tree_json_string` documents and selected font slugs. It reports property paths
without dumping the document or executing embedded HTML/PHP. Exports remain on
the machine; don't commit real site exports or private reports to this tool.

Use `--runtime-json /path/to/runtime-observations.json` for facts collected through
an authenticated read-only API or authorized WP-CLI workflow:

```json
{
  "breakdance_version": "3.0.2",
  "last_migrated_version": "3.0.2",
  "activation_marker": "yes",
  "font_slugs": ["gfont-sourcesans3"]
}
```

Omit facts that have not been collected. `last_migrated_version: null` means the
marker was actually checked and absent. The script flags this for investigation;
it does not call it the cause of a CSS regression. A supplied `font_slugs` list
must contain the complete registered runtime list, including custom font aliases.
Without that list, font comparisons use the bundled Google font list and explicitly
identify that limitation.

The native migration marker can be read with:

```sh
wp option get breakdance_last_migrated_version
```

Breakdance JSON-encodes this value in the option. Normalize it to the plain version
string in the observation file. A missing marker needs separate native initialization
investigation; don't set a fake version, reset the site, or rerun setup to silence it.
On SpinupWP, use the installed CLI's documented read-only transport for observations;
this script never opens SSH or bypasses its command gate.

`wordpress-observations.php` collects these observations from the actual runtime,
including both initialization markers, design switches, cache flag, fallback
fingerprints and icon-set counts. Add `fonts` for the complete registered font list.
The helper body makes no writes; WordPress still performs its normal bootstrap.

```sh
wp --path=/path/to/wordpress --user=administrator \
  eval-file /path/to/toolkit/wordpress-observations.php fonts > /tmp/runtime.json
```

Use this through an already-authorized local/MCP/CLI workflow. The installed
SpinupTUI read-only gate may reject `eval-file` or even post-meta exports; do not
wrap a denied command or use raw SSH to bypass it. The fixture below provides
shared-component validation when a source-site export is unavailable.

Exit status is 0 for a completed report, 1 for invalid input/failed execution, and
2 with `--strict` when findings or coverage gaps need review. A zero exit status
without `--strict` does not mean a site passed visual compatibility testing.
Missing input directories fail, and empty evidence cannot produce a strict pass.
Versions outside v2/v3 are flagged as unsupported. The specific packages validated
for this initial release are 2.8.3 and 3.0.2; other releases still need native
contract and staging validation.

## What the review established

The exact 3.0.2 package was compared with 2.8.3. In `plugin/render/renderer.php`,
`getElementSelector()` changes from `.breakdance .element-class` to `.element-class`.
Selector and preset output also loses the wrapper. This changes specificity even
when stored control values and legacy definitions survive.

[Breakdance's 3.0 announcement](https://breakdance.com/meet-breakdance-3-0/) describes
the new selector format. Its [native migration documentation](https://breakdance.com/documentation/other/migrate-legacy-selectors/)
preserves old selector definitions and offers an optional conversion. Converting
them is not a general remedy for custom CSS that assumes the old specificity.

The native upgrader handles earlier schema changes and version bookkeeping; the
reviewed 3.0.2 code contains no general custom-CSS intent migration. An update tool
cannot determine whether an authored broad selector should outrank a native button
without inspecting the intended design.

| Finding | Owner and reusable treatment |
| --- | --- |
| Local code differs from lockfile or reviewed runtime | Deployment review prerequisite; inspect the exact package first. |
| Shared spacing defaults outweigh element controls | Creare Bootstrap extension; fix its source with deliberately low-specificity defaults. |
| Broad link color or weak SVG custom rule | Authored CSS; inspect computed styles and propose a scoped source correction. |
| Body class concatenated with `%%SELECTOR%%` | Authored CSS; verify the body ancestor, then correct the smallest saved CSS field. |
| Unresolved legacy Google font slug | Font registry issue; may predate v3. Confirm custom registrations before mapping it. |
| Missing search widget initialization | Dependency/rendering issue; the Search Form V1 JavaScript was identical in 2.8.3 and 3.0.2. |
| Custom FacetWP checkbox, calendar link, zero-price wallet error | Site/integration maintenance; never general migration defaults. |

The shared 1.4.3 correction is version-aware. Its own template uses
`%%BOOTSTRAP_DEFAULT_SCOPE%%`, resolved by its append callback to `.breakdance` on
V2 and `:where(.breakdance)` on V3. Palette selectors and other extensions' appended
templates stay unchanged. Both the editor and frontend use that resolved template.

An unconditional `:where()` change was rejected during staging: it let V2's native
heading reset defeat an unset element's shared spacing default. The corrected V2
template is byte-for-byte identical to the 1.4.2 baseline. The low-specificity scope
is used only on V3; other major versions require a fresh review. The source commit
records the change against 1.4.2. Make changes in the owning source checkout
or its `-dev` source, following the normal release workflow; do not edit a
Composer-managed installed copy.

The deployed site's render-time adapter is a reversible frontend hotfix. It keeps
the saved document unchanged; this also means saved invalid custom CSS remains.
Its original unit tests check transformations, not the real editor. The shared
Bootstrap/native-control behavior has now been tested in a real editor fixture;
that does not establish editor parity for a site's original custom CSS under a
render adapter. Test those affected documents separately before removing an adapter.

## Staging validation and initialization findings

The shared candidate was tested on WordPress 7.1.2 with real Breakdance 2.8.3 and
3.0.2 packages, without the site-specific render adapter. A genuine package upgrade
from 2.8.3 to 3.0.2 advanced the native version marker and preserved the saved tree.

| Control | V2 legacy / corrected | V3 corrected desktop | V3 corrected phone |
| --- | --- | --- | --- |
| Heading with native override | 35px | 35px | 35px (1.25em at 28px font) |
| Button with native override | 32px | 32px | 24px |
| Heading using shared default | 17.5px | 17.5px | 14px |
| Paragraph defaults, including last paragraph | 9px | 9px | 9px |

V3's live builder preview matched frontend measurements on desktop and phone. The
editor's own WebMCP `builder_save` saved the fixture successfully; values survived a
full reload. Native parent-reference normalization was identified separately from
control changes, and the next client save preserved the complete tree fingerprint.

Initialization was tested on a separate disposable database with custom icon,
fallback and design-setting probes, then rolled back from a local snapshot:

- The native Total Reset sequence (`reset(); install();`) removed both markers and
  did not recreate them. Normal loads and CSS regeneration left them missing.
- With both markers absent, native version handling returns early. This is an
  initialization-bookkeeping issue, separate from selector specificity and cache
  generation. It is a reproduced cause of this state, not proof of a particular
  site's history.
- Native first initialization recreated the markers but removed the custom icon
  set, replaced fallback IDs, and forced theme disabling, skip link and Modern
  Normalize on. Reactivation is therefore not a harmless bookkeeping refresh.
- Snapshot restoration recovered the custom icon set, original fallback IDs,
  design switches and genuine markers exactly.

The upgrade workflow stops for missing or invalid initialization markers. Prefer
recovering genuine values from a verified backup and letting the native updater
run. Otherwise, test a state-preserving native recovery on a full clone, covering
icon schemas/data, fallback IDs/settings, documents, globals and design switches.
There is intentionally no automatic activation, reset or guessed-version repair.
Never synchronize a staged commerce database back over live transactions.

## Repeatable isolated fixture

`staging-fixture.php` is a separate, mutating test helper. Use a new disposable
WordPress install, not a copy containing real authored documents. Activate the
exact Breakdance package and a source copy of Bootstrap, choose an administrator,
set `WP_ENVIRONMENT_TYPE` to `local`/`staging`, and explicitly define
`CREARE_BD_DISPOSABLE_FIXTURE` as `true` in that fixture's config.

`seed` sets global spacing and the front page, so it refuses existing global
settings and all authored Breakdance document types, allowing only exact bundled
stock fallback definitions. It never creates accounts. Its helper actions are:

```sh
wp --path=/path/to/disposable/wp --user=administrator eval-file /path/to/toolkit/staging-fixture.php seed
wp --path=/path/to/disposable/wp --user=administrator eval-file /path/to/toolkit/staging-fixture.php inspect
wp --path=/path/to/disposable/wp --user=administrator eval-file /path/to/toolkit/staging-fixture.php clear-cache
wp --path=/path/to/disposable/wp --user=administrator eval-file /path/to/toolkit/staging-fixture.php resave
```

The `resave` action calls the native editor save handler and preserves future-layer
metadata. Also open the real builder, measure its preview, use its supported native
MCP save API, and reload it; the PHP handler alone does not test client serialization.
Read WebMCP tools afresh after navigation, and use `builder_get_document_info` before
acting. Inspect stored values afterward and distinguish valid parent-reference
normalization from actual control/ID/content changes.

## Reusable upgrade procedure

1. Capture a restorable code/database backup and a representative desktop/mobile
   visual baseline in staging. Record native plugin, migration, and selector state.
2. Run this preflight with the exact source and exported header/page/global CSS.
   Resolve source-version drift and initialization findings before proceeding.
3. Update the intended Breakdance package through the project's normal dependency
   workflow, scoped to Breakdance and the shared Bootstrap release. Verify actual
   installed versions against the lock. On tested V3, `wp breakdance clear_cache`
   is the native CLI cache operation; inspect the installed help before using it.
   Run writes through an authorized API or normal deployment helper, then purge
   the site's external page/optimization caches and warm representative pages.
   Keep native legacy selectors unless conversion is independently wanted.
4. Fix confirmed extension conflicts at their owning source. Change only verified
   authored CSS fields through supported content APIs with revision/precondition
   checks. Keep IDs, responsive values, bindings, and native editor ownership.
   If content-write access is unavailable, label any render-time adapter explicitly.
5. Run preflight again. Verify computed styles plus desktop/tablet/mobile navigation,
   shop filters, products, populated cart/checkout, forms, and browser errors. Check
   editor preview and re-save behavior. HTTP 200 and asset checks alone are insufficient.
6. Deploy the reviewed changes, warm representative pages, and verify the actual
   runtime revision. Retain the baseline and the per-site changes for rollback.

Rollback means restoring the reviewed code/content state through normal recovery
paths, regenerating the native CSS/dependency caches, purging external caches, and
warming the pages again. Removing an adapter's PHP file alone does not remove its
already-generated CSS. Use native regeneration rather than broad metadata deletion
as a new fleet default.

## Tests

```sh
python3 -m unittest discover -s tools/breakdance-upgrade -p 'test_*.py' -v
php tools/breakdance-upgrade/test-shared-spacing.php /path/to/bootstrap 2.8.3
php tools/breakdance-upgrade/test-shared-spacing.php /path/to/bootstrap 3.0.2
```

These test evidence classification, nested exports, version drift, network scope,
non-destructive report writing and review exit codes. They do not substitute for
WordPress or browser integration checks. The Python preflight intentionally has no
`--apply`; the fixture is restricted to disposable non-production installations.

To check the actual selector functions in a trusted extracted package:

```sh
php tools/breakdance-upgrade/native-selector-contract.php /path/to/extracted/breakdance
```

This loads only the package's renderer function definitions and calls its native
selector/tag functions. Run it separately for the old and new packages to record
the actual prefix change. It executes trusted plugin PHP, so use the normal
Composer/vendor package, not an arbitrary untrusted upload. It does not boot
WordPress or establish editor parity.
