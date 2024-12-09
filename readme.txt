=== CamCraft ===
Contributors: yourusername
Donate link: https://yourwebsite.com/donate
Tags: camera, image editing, 3D objects, WordPress, customization
Requires at least: 5.6
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful plugin that integrates live camera functionality with advanced editing tools to overlay and customize 3D objects directly on captured images.

== Description ==

**CamCraft** revolutionizes how you interact with cameras and images in WordPress. This plugin offers a seamless way to capture live camera feeds, overlay customizable elements, and edit your images on-the-go. Perfect for creatives, photographers, and anyone looking to add a unique touch to their media.

### Key Features:
- Capture live camera feeds directly from your browser.
- Overlay and customize 2D and 3D elements on captured images.
- Drag, resize, and rotate elements with ease.
- Save your edited images directly to the WordPress Media Library.
- Download edited images instantly for offline use.
- Fully compatible with modern browsers and responsive designs.
- Designed for both beginners and advanced users.

**Built with the future in mind**, this plugin allows for future enhancements, including advanced 3D object integration, dynamic filters, and more.

== Installation ==

1. Download the plugin from the WordPress Plugin Directory.
2. Upload the `camcraft` folder to your WordPress `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Access the plugin settings in the WordPress admin dashboard under **"CamCraft"**.

== Screenshots ==

1. **Live Camera Feed**: Easily access and interact with your camera feed in real-time.
2. **Customizable Overlays**: Add and customize elements directly on your images.
3. **User-Friendly Interface**: Intuitive design for smooth editing experiences.
4. **Save & Download Options**: Save your work directly to the media library or download instantly.

== Frequently Asked Questions ==

= Does this plugin support all browsers? =
CamCraft works on all modern browsers that support `getUserMedia`. However, for older browsers, some features may not be available.

= Can I use this plugin for 3D objects? =
Yes! The plugin is designed to support 3D objects using WebGL technology, with seamless integration planned for future updates.

= Are the saved images stored securely? =
All images are saved in the WordPress Media Library and follow the standard WordPress file handling process.

== Changelog ==

= 1.0.0 =
* Initial release.
* Core features: camera feed, 2D/3D overlay editing, and image saving.

== Future Roadmap ==

- Integration with Three.js for advanced 3D object manipulation.
- Support for dynamic filters and advanced editing tools.
- Enhanced compatibility with WooCommerce for product customization.

== Technical Details ==

- **Core Technologies Used**:
  - HTML5 Camera API (`getUserMedia`) for live camera feed integration.
  - JavaScript (ES6+) for interactivity and editing tools.
  - Canvas API for 2D rendering and editing.
  - WebGL (via Three.js) for future 3D object support.
  - AJAX for smooth communication with the WordPress backend.
  - PHP (7.4+) for server-side logic and media handling.

- **Performance Optimizations**:
  - Asynchronous rendering to ensure smooth user experience.
  - Lightweight asset loading for faster page loads.

- **Security**:
  - Sanitized inputs to prevent XSS attacks.
  - WordPress Nonce verification for secure AJAX requests.

== License ==

This plugin is licensed under the GPLv2 or later. See the [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html) for full details.

== Support ==

If you encounter any issues or have suggestions for improvement, please contact us at [support@yourwebsite.com](mailto:support@yourwebsite.com).
