# Breakdance Bootstrap Plugin
Bootstrap features for Breakdance sites

## Changelog
### 10/21/25 v1.2.6
- Forgot to not load the Turnstile field if there's no key or no action chosen. Duh.
### 10/20/25 v1.2.5
- Fix failed Turnstiles from getting submitted
### 10/1/25 v1.2.4
- Fix for FAQ w/ Schema not loading JS properly in BD 2.5.1
### 9/8/25 v1.2.3
- Fix for Breakdance ACF relationship field ordering not working by ACF field
### 6/13/25 v1.2.2 
- Version bump and release of beta changes.
### 6/10/25 v1.2.1-beta.4-5 ###
- Add repeater field functionality to Google Maps Locations widget
### 5/7/25 v1.2.1 
- Add filter to apply Breakdance button primary style to Gutenburg buttons
### 5/5/25 v1.2.0
- Add new Table of Contents for SEO element
- - Checks if the headings in the post already have IDs and doen't add them again, or uses existing ones
- - Accounts for excluded headings via the TOC cache hash
- - Updates `the_content` in the main post table when running through the post for the first time.
- - We now selectively run the_content filter based on the $updated_contents when posts were changed and the page was loaded for the first time. Subsequent loads are already stored to $post->post_content.
- - Beta.2
- - - Add spacing bars for container

### 4/11/25 v1.1.2
- Add more variable checks when loading color palette. Fresh installs may suffer from not having things set.
- Add human usable color palette variables in :root like `--bde-palette-color-blue`
- Add css rules to then use these colors in Gutenburg posts, etc. where it outputs `has-bd-palette-blue-color`
### 4/8/25 v1.1.1
- Fix fatal error when trying to load global colors into the theme color palette
### 4/5/25 v1.1.0
- Add support to show Breakdance palette colors in the Gutenburg palette (for FluentCRM/others)
### 3/31/25 v1.0.10
- Add filter bricbd_accordion_title to edit the copy that appears in a Post Loop Builder accordion
### 3/31/25 v1.0.9
- Fix global css style for margin on buttons
### 3/27/25 v1.0.6/7/8
- Add "Press List" element
- I'm bad at version control and I fixed a bunch of things that didn't work when i tagged the release
- Add target blank
### 3/17/2025 v1.0.5
- Require Breakdance in wp plugin header
### 3/14/2025 v1.0.4
- Implement Breakdance layout v2 macro in the Website policies element
### 2/16/2025 v1.0.2
- Improvements to fluentcrm form submission provider
### 1/21/2025 v1.0.1
- Fix for Safari on the StickyHeaderFix script. Switch :first-of-type for the explicit approach, calling the first item in the array [0]
### 1/20/2025 v1.0.0 - MLK Day & Innauguration Day of Trump the 2nd time around
- Add a Fluent Form Provider for Breakdance form submissions. Matches submissions with the email of the subscriber and lists them in the Form Submissions section.
- Added a helper debug function, `dlog( $stuff, $name )`
- Add experimental code for tying a user/email to Fluent record. This is still in progress. I need to find a way to record the tracking ID (cookie) with the Breakdance form submission (maybe on the wp_insert_post hook). Then the tracking ID needs to be stored as a meta value for the Subscriber.
### 1/14/2025 v0.9.9
- Hotfix for Breakdance\AJAX\BreakdanceAjaxHandlerException: Calling get_class() without arguments is deprecated in /sites/marjiam.com/files/web/app/plugins/breakdance/plugin/ajax/api.php on 116
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