# p5.js Activity for Moodle

A modern, interactive activity module for Moodle that allows students to create, run, and share p5.js sketches directly within their browser.

## Features

- **Multi-file Editor**: Create and manage multiple files (`.js`, `.css`, `.html`) within a single sketch.
- **Live Preview**: Real-time preview of your sketch in an isolated sandbox iframe.
- **Auto-Refresh**: Automatically updates the preview as you type code.
- **Save & Resume**: All sketch data is saved per user directly to the Moodle database.
- **Responsive Interface**: Sleek, modern UI with a collapsible sidebar and flexible layout.
- **Instance Support**: Pre-configured to support the latest version of the p5.js library.

## Installation

1. Copy the `p5js` folder into your Moodle installation's `mod/` directory.
2. Login to your Moodle site as an administrator.
3. Go to **Site Administration > Notifications** to trigger the installation of the plugin.
4. Follow the on-screen instructions to complete the setup.

## Usage

1. Navigate to a course and **Turn editing on**.
2. Click **Add an activity or resource**.
3. Select **p5.js Activity** from the list.
4. Give your activity a name and description.
5. Save and display the activity.
6. Use the integrated editor to write your code:
   - Edit `sketch.js` for your main logic.
   - Use the **Play** button to run the sketch or toggle **Auto-refresh**.
   - Use **Save All** to persist your changes.

## License
GNU General Public License v3 or later.
