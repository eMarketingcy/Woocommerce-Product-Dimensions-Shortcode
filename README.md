# WooCommerce Product Dimensions Shortcode

A WordPress plugin that adds shortcodes to display WooCommerce product dimensions and calculate JIS battery codes for Japanese battery types.

## Description

This plugin provides two main features:

1. **Product Dimensions Shortcode** - Display product shipping dimensions with the correct unit from WooCommerce settings
2. **JIS Code Calculator** - Automatically calculate and display Japanese Industrial Standard (JIS) battery codes based on product dimensions and specifications

## Features

- Display product dimensions using `[product_dimensions]` shortcode
- Automatic JIS code generation for Japanese battery products
- Configurable settings page under WooCommerce menu
- Full internationalization support (i18n ready)
- Respects WooCommerce dimension unit settings (cm, in, m, etc.)
- Security-hardened with proper capability checks and validation
- Optional ACF integration for JIS code functionality
- Admin notifications when dependencies are missing

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.2 or higher
- **WooCommerce**: Latest version recommended
- **Advanced Custom Fields (ACF)**: Required only for JIS code functionality (optional)

## Installation

1. Upload the plugin files to `/wp-content/plugins/woocommerce-product-dimensions/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings under **WooCommerce > Dimensions Settings**

### Configuration for JIS Code Functionality

If you want to use the JIS code calculator:

1. Install and activate Advanced Custom Fields (ACF)
2. Navigate to **WooCommerce > Dimensions Settings**
3. Enable "Enable JIS Code Functionality"
4. Configure the following settings:
   - **JIS Category ID**: The product category ID for Japanese battery products
   - **ACF JIS Code Field Key**: The ACF field key where the JIS code will be saved (e.g., `field_67c437ef655d2`)
   - **ACF AH Field Name**: The ACF field name for battery performance rank (default: `ah`)
   - **ACF Terminals Field Name**: The ACF field name for terminal position (default: `terminals`)

## Usage

### Product Dimensions Shortcode

Display dimensions in product pages, posts, or anywhere shortcodes are supported:

```
[product_dimensions]
```

**With specific product ID:**
```
[product_dimensions product_id="123"]
```

**Output example:**
```
30 × 17.3 × 22 cm
```

The unit (cm, in, m, etc.) is automatically pulled from your WooCommerce settings.

### JIS Code Shortcode

Display JIS battery code for Japanese battery products:

```
[jis_code]
```

**With specific product ID:**
```
[jis_code product_id="123"]
```

**Output example:**
```
46D23L
```

### JIS Code Calculation Logic

The JIS code is automatically calculated based on:

1. **Performance Rank (Ah)**: From ACF field (e.g., 46)
2. **Width Letter**: Determined from product width with ±3cm tolerance:
   - A = 12.7 cm
   - B = 12.9 cm
   - D = 17.3 cm
   - E = 17.6 cm
   - F = 18.2 cm
   - G = 22.2 cm
   - H = 27.8 cm
3. **Length**: Product length rounded (e.g., 23)
4. **Terminal Position**: L (Left) or R (Right) from ACF field

**Example**: `46D23L` = 46Ah performance, D width (17.3cm), 23cm length, Left terminal

## Automatic JIS Code Updates

When enabled, the plugin automatically:

- Calculates JIS codes when products are saved/updated
- Stores the code in the configured ACF field
- Only processes products in the specified category
- Removes invalid codes automatically

## Settings Page

Access the settings page under **WooCommerce > Dimensions Settings** to configure:

- Enable/disable JIS code functionality
- Set category ID for Japanese battery products
- Configure ACF field mappings
- Customize field names

## Security Features

- Capability checks (only users with `edit_post` permission can update products)
- Input validation and sanitization on all user inputs
- Proper escaping of output to prevent XSS
- Protection against CSRF attacks
- ACF dependency checks to prevent fatal errors

## Filters and Customization

The plugin uses standardized function naming with the `wpds_` prefix for easy customization.

### Available Functions

- `wpds_get_product_dimensions($product)` - Get dimensions for a specific product
- `wpds_calculate_jis_code($product_id)` - Calculate JIS code for a product
- `wpds_check_acf_active()` - Check if ACF is available

## Translation Ready

The plugin is fully internationalized and ready for translation:

- Text domain: `woocommerce-product-dimensions`
- Domain path: `/languages`
- All user-facing strings are translatable

To translate:
1. Use a translation plugin like Loco Translate or WPML
2. Translate strings in the `woocommerce-product-dimensions` text domain

## Changelog

### Version 2.0.0 (2026-01-18)
- Complete rewrite with major improvements
- Added configurable settings page
- Removed hardcoded category and field IDs
- Added ACF dependency checks
- Implemented proper security measures
- Added internationalization support
- Fixed dimension unit to respect WooCommerce settings
- Standardized function naming conventions
- Added comprehensive input validation
- Added product_id parameter support for both shortcodes
- Improved error handling and edge case management

### Version 1.4
- Initial version with basic functionality

## Support

For issues, feature requests, or contributions:
- Visit: https://emarketing.cy

## License

This plugin is licensed under GPL2.

## Credits

Developed by Omar Tamim - https://emarketing.cy

## Frequently Asked Questions

### Do I need ACF for the dimensions shortcode?

No, the `[product_dimensions]` shortcode works independently without ACF. ACF is only required for the JIS code functionality.

### Can I use this with variable products?

Yes, the shortcodes work with simple and variable products. For variable products, dimensions are pulled from the variation.

### How do I find my category ID?

1. Go to Products > Categories in WordPress admin
2. Hover over or edit the category
3. Look at the URL - the ID is shown as `tag_ID=XXXX`

### How do I find my ACF field key?

1. Edit your ACF field group
2. Click on the field
3. The field key is shown below the field label (e.g., `field_67c437ef655d2`)

### The JIS code is not displaying

Check the following:
1. ACF plugin is installed and active
2. JIS code functionality is enabled in settings
3. All required settings are configured (category ID, field keys)
4. The product belongs to the specified category
5. Required ACF fields (ah, terminals) have values
6. Product has valid dimensions (length and width)

### Can I customize the output HTML?

Yes, you can use CSS to style the output. The dimensions use class `product-dimensions` and JIS code uses class `jis-code`.

Example CSS:
```css
.product-dimensions {
    font-weight: bold;
    color: #333;
}

.jis-code {
    background: #f0f0f0;
    padding: 5px 10px;
    border-radius: 3px;
}
```
