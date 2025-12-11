# OfflineQuiz Addons Plugin for Moodle

A local plugin that extends the OfflineQuiz module functionality without modifying its core code.

## Description

This plugin adds a new tab to all OfflineQuiz activities, providing a foundation for extending OfflineQuiz capabilities with custom features, reports, and tools.

## Features

- ✅ Adds a custom "Addons" tab to OfflineQuiz module pages
- ✅ Clean architecture with modern Moodle standards (namespaces, renderers, templates)
- ✅ Capability-based access control
- ✅ No modifications to core OfflineQuiz code
- ✅ Easy to maintain and extend

## Requirements

- Moodle 4.0 or higher
- OfflineQuiz module (`mod_offlinequiz`) must be installed

## Installation

### Method 1: Via Moodle UI (Recommended)

1. Download the plugin as a ZIP file
2. Log in to your Moodle site as an administrator
3. Go to `Site administration → Plugins → Install plugins`
4. Upload the ZIP file
5. Click "Install plugin from the ZIP file"
6. Follow the on-screen instructions

### Method 2: Manual Installation

1. Extract the ZIP file
2. Copy the `offlinequizaddons` folder to `[moodleroot]/local/`
3. Visit `Site administration → Notifications` to complete the installation
4. The plugin will be installed automatically

## Usage

1. Navigate to any OfflineQuiz activity in your course
2. You will see a new "Addons" tab in the activity navigation
3. Click on the tab to access the addon features (currently displays "Hello World")

## Directory Structure

```
local/offlinequizaddons/
├── classes/
│   └── output/
│       ├── mainpage.php       # Renderable class for main page
│       └── renderer.php        # Plugin renderer
├── db/
│   └── access.php              # Capability definitions
├── lang/
│   └── en/
│       └── local_offlinequizaddons.php  # English language strings
├── templates/
│   └── mainpage.mustache       # Main page template
├── lib.php                     # Library functions and callbacks
├── version.php                 # Plugin version information
├── view.php                    # Main view page
├── README.md                   # This file
└── .gitignore                  # Git ignore rules
```

## Capabilities

- `local/offlinequizaddons:view` - View the addons tab (granted to teachers and managers by default)

## Development

This plugin is designed with extensibility in mind. Future enhancements can include:

- Custom reports and analytics
- Additional quiz management tools
- Enhanced export/import features
- Custom settings and configurations
- AJAX-powered interactive features

### Adding New Features

1. Create new classes in `classes/` following PSR-4 autoloading
2. Add new templates in `templates/` directory
3. Extend `lib.php` with additional callbacks as needed
4. Add language strings in `lang/en/local_offlinequizaddons.php`

## Version Control

This plugin uses Git for version control. To track your changes:

```bash
cd local/offlinequizaddons
git add .
git commit -m "Your commit message"
```

## Support

For issues, questions, or contributions, please contact the plugin maintainer.

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

## Credits

Developed following Moodle coding guidelines and best practices.

## Changelog

### Version 1.0.0 (2025-12-11)
- Initial release
- Added "Addons" tab to OfflineQuiz activities
- Basic "Hello World" demonstration page
- Clean, maintainable architecture
