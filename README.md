# Breakdance Bootstrap Plugin
Bootstrap features for Breakdance sites

## Changelog
### 1/7/2025 v0.9.8
- Include local versions of GSAP and ScrollTrigger.js.
### 12/13/2024 v0.9.7
- Add options for sticky header fix element - extra padding and if you're pushing the entire div or just the internal components (text)
### 11/17/2024 v0.9.6
- Improve dynamic accordion
- Improve Child Page element
- Create Sticky Header Fix element o automatically add appropriate margin/padding
### 10/3/2024 v0.9.5
- Fix messed up redirect on multisite due to the fluent crm code trying to keep subscribers out of the backend.
### 9/27/2024 v0.9.4
- Add Term Icon Element so you can display a term as an icon for a post
### 9/20/2024 v0.9.3
- Improve Fluent CRM action. Allow real double opt-in now. New setup enables a "check to be added to list" with checkbox or hidden field value to "pending".
- Add Element for Child Pages navigation
- Add WP User form action to add form submission as user
- Add auto-login script when optin link is clicked...allows direct tracking of mailing list subscribers to their user.
- Tweak RezStream element
### 9/16/2024 v0.9.2
- Add RezStream Element in BD builder
### 9/11/2024 v0.9.1
- Fix the prev/next controls on loops with the popup link. These now group using the Breakdance action breakdance_posts_loop_before_loop when a new post loop is being processed.

### 8/22/2024 v0.9
- Add the popup element

### 8/15/2024 v0.8
- Add Suffix field to copyright

### 8/12/2024 v0.71
- Tweak styles on the policies links
- Fix Maps not rendering in builder due to improper handling of objects

### 8/8/2024 v0.7
- Add filter to add Breakdance class to FWP reset button
- Add Google Maps Locations Element
- Add elements for footer: List Items, Website Copyright, Website Author, Policies Section

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