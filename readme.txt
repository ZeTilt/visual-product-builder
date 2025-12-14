=== Visual Product Builder - Product Customizer for WooCommerce ===
Contributors: alreweb
Donate link: https://ko-fi.com/alreweb
Tags: woocommerce, product customizer, product designer, personalization, custom products
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let your customers create custom product designs with an intuitive visual configurator. Perfect for personalized gifts, jewelry, and decorations.

== Description ==

**Visual Product Builder** is a powerful yet simple WooCommerce extension that allows your customers to create personalized product designs by combining visual elements like letters, numbers, and shapes.

Unlike complex product designers, Visual Product Builder focuses on **linear element placement** - perfect for products like name bracelets, pacifier clips, letter garlands, and personalized decorations.

= Perfect For =

* Personalized jewelry (charm bracelets, name necklaces)
* Baby accessories (pacifier clips with names)
* Home decoration (letter garlands, door signs)
* Custom gifts (personalized keychains, bags)
* Any product with linear element arrangement

= Key Features =

* **Intuitive Interface** - Click to add elements, drag to reorder
* **Real-time Preview** - See the design update instantly
* **Collection System** - Organize elements by color or theme
* **Dynamic Pricing** - Price updates automatically based on selected elements
* **Support Images** - Display elements on a product background image
* **Mobile Optimized** - Works perfectly on all devices
* **Cart Integration** - Designs are saved with orders
* **Image Generation** - PNG preview attached to orders
* **Undo/Reset** - Easy design corrections
* **Auto-save** - Designs saved in browser (crash protection)
* **Custom CSS** - Shop owners can customize the appearance

= How It Works =

1. Create collections of elements (letters, numbers, symbols)
2. Assign collections to your WooCommerce products
3. Add the shortcode to your product page
4. Customers build their design and add to cart
5. You receive the order with a preview image

= Pro Features (Coming Soon) =

* Unlimited elements and collections
* Stock management per element
* Quantity discounts and bundles
* CSV import/export
* Analytics dashboard
* Priority support

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 5.0 or higher
* PHP 7.4 or higher

== Installation ==

1. Upload the `visual-product-builder` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **WooCommerce > VPB Settings** to configure
4. Add elements to your library via **WooCommerce > VPB Elements**
5. Create collections via **WooCommerce > VPB Collections**
6. Use shortcode `[vpb_configurator]` on any product page

= Quick Start =

1. After activation, go to **VPB Settings**
2. Click "Import Sample Data" to load demo elements
3. Edit a WooCommerce product and enable Visual Product Builder
4. Add the shortcode `[vpb_configurator]` to the product description
5. Your configurator is ready!

= Shortcode Attributes =

* `product_id` - Product ID (auto-detected on product pages)
* `limit` - Maximum number of elements (default: 10)

Example: `[vpb_configurator product_id="123" limit="15"]`

== Frequently Asked Questions ==

= Does this work with any WooCommerce theme? =

Yes! Visual Product Builder is designed to work with any properly coded WooCommerce theme. We test with popular themes like Storefront, Flatsome, and Astra.

= Can I use my own images? =

Yes, you can upload SVG, PNG, JPG, GIF, or WebP images as elements. The admin panel allows easy upload and management.

= Is it mobile-friendly? =

Absolutely! The configurator is fully responsive and optimized for touch devices, including drag and drop reordering.

= Can I limit the number of elements? =

Yes, you can set a maximum element limit per product using the shortcode attribute or in the product settings.

= How are custom designs saved? =

Designs are saved as order metadata and a PNG image is generated and attached to the order for easy reference.

= Can I customize the appearance? =

Yes! Go to VPB Settings and use the Custom CSS field to adjust colors, sizes, and styling to match your theme.

= Is the plugin translation-ready? =

Yes, the plugin is fully internationalized and works with WPML, Polylang, and other translation plugins. A French translation is included.

= Where can I get support? =

For the free version, please use the WordPress.org support forums. We check them regularly and will help you troubleshoot any issues.

== Screenshots ==

1. Frontend configurator with real-time preview
2. Element library with collection filtering
3. Admin: Element management interface
4. Admin: Collection management
5. Order view with custom design preview
6. Mobile responsive view

== Changelog ==

= 1.0.0 =
* Initial release
* Element library management with categories
* Collection system for organizing elements
* Frontend configurator with real-time preview
* Drag and drop element reordering
* Support image feature (background for elements)
* Dynamic pricing based on elements
* Cart and order integration
* PNG image generation attached to orders
* LocalStorage auto-save (crash protection)
* Undo and reset functionality
* Custom CSS field for shop owners
* Responsive design for all devices
* French translation included

== Upgrade Notice ==

= 1.0.0 =
Initial release of Visual Product Builder. Create beautiful product configurators for your WooCommerce store!

== Privacy Policy ==

Visual Product Builder does not collect any personal data. Design configurations are stored locally in the browser (localStorage) and in WooCommerce orders on your own server.

No data is sent to external servers. The plugin does not use cookies for tracking purposes.
