# Breakdance Bootstrap Plugin
Bootstrap features for Breakdance sites

## Changelog
### 5/8/24 v0.6
- Add in the gated content popups system. You can now make a popup as a global block template and have it apply to any particual DLM download
- TODO: check for DLM plugin
- TODO: finish action for Fluent CRM, add a way to tag/log real email in crm (this is easy with Fluent since it runs on the site, but maybe we want to give other CRMS a way to get this data, perhaps via a Breakdance form post action when the user clicks on that download link)

### 4/17/24 v0.5
- Add an improved version of the Stats Grid element
- Re-arrange code for easier extensibility

### 2/23/24 v0.4.1
- fix stuff
### 2/23/24 v0.4
- Change namespace for elements

### 2/22/24 v0.3
- Add BD element for Jobvite embed

### 2/19/24 v0.2
- Added custom breakdance blocks for Dynamic Accordion so you can load an accordion from post types
- Commented out the loading of accordion js and css for the old way which looked for classes present. TODO: figure out what to do for backwards compat or just let it break and update the site so it uses the new block