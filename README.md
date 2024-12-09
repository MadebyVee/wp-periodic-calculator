=== Periodic Payment Calculator Plugin ===

Contributors: [Your Name]  
Tags: payments, financing, loan calculator  
Requires at least: 5.6  
Tested up to: 6.3  
Stable tag: 1.0.0  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

This plugin enables dynamic periodic payment calculations for RV financing, with customizable settings and frontend shortcodes for user interaction.

== Description ==

The Periodic Payment Calculator Plugin is a powerful tool designed for RV financing solutions. It allows administrators to configure amortization terms, meta settings, and RV type fees in the backend while providing simple shortcodes for frontend integration.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.  
2. Activate the plugin through the 'Plugins' menu in WordPress.  
3. Navigate to **Dashboard > Periodic Payment Settings** to configure settings.  

== Usage ==

### Backend Settings:
1. **Meta Setting**  
   Navigate to **Dashboard > Periodic Payment Settings > Meta Setting**.  
   - A table with 3 columns is provided:  
     - **Column 1**: Meta types: Regular, Dealer, and Sale Price.  
     - **Column 2**: Input to assign the meta key associated.  
     - **Column 3**: Post type slug (this should not be changed).

2. **Term & Amortization**  
   Navigate to **Dashboard > Periodic Payment Settings > Term & Amortization**.  
   - Table includes:  
     - **Year of Vehicle**: Enter the applicable year.  
     - **Amortization**: Set the amortization value.  
     - **Term**: Set terms in months based on industry standards. Adjust if necessary.

3. **RV Types**  
   Navigate to **Dashboard > Periodic Payment Settings > RV Types**.  
   - Configure fees for specific RV Types (leave values at 0 if no fee is applicable).  
   - Do not delete existing RV Types; you can add new types as needed.  
   - **Note**: RV Types will be locked in the next release.

4. **Settings**  
   Navigate to **Dashboard > Periodic Payment Settings > Settings**.  
   - Configure general settings:  
     - Interest Rate  
     - Tax Rate  
     - Loan Fee  
     - Taxed Fee (tax-inclusive fee for financing).  

### Frontend Shortcodes:
- `[rv_financing_calculator]`  
  Displays a detailed loan calculator for RV financing, allowing users to calculate payments based on term, rate, and other parameters.

- `[payment]`  
  Display biweekly payments only.  

- `[calculator]`  
  Integrate a form allowing users to calculate payments weekly, biweekly, or monthly.  

- `[payment data=cob]`  
  Show total cost of borrowing with loan details (term, amortization) in fine print.  

### Customization:
- Edit `style.css` in the plugin folder to style the frontend display.

== Changelog ==

= 1.0.0 =
* Initial release with backend settings and frontend shortcodes.

== Frequently Asked Questions ==

**Q: Can I change the RV Types in the settings?**  
A: Yes, but do not delete any existing RV Types. In the next release, RV Types will be locked.

**Q: How do I add new terms or amortization values?**  
A: Navigate to **Term & Amortization** and update the table as needed.

**Q: Can I style the shortcodes?**  
A: Yes, modify `style.css` for custom styling.

== Support ==
For support, please contact [Your Support Email or Website].
