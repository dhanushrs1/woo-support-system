# 🚀 WooCommerce Support System 🚀

A complete, modern, and professional support ticket system that seamlessly integrates into your WooCommerce "My Account" page. Empower your customers and streamline your entire support workflow.

| | |
| :--- | :--- |
| **Contributors:** | dhanushrs1 |
| **Stable Tag:** | `3.0.6` |
| **Requires:** | WordPress `5.0` / PHP `7.4` |
| **Tested Up To:** | WordPress `6.5` |
| **License:** | [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html) |

---

## ✨ Turn Customer Questions into Customer Loyalty ✨

Stop juggling messy support emails and give your customers the integrated, professional experience they deserve. The **WooCommerce Support System** transforms your e-commerce store into a powerful helpdesk without the clunky feel of a third-party add-on.

It provides a simple and intuitive way for customers to get help, and a robust backend for you to manage, track, and resolve issues with maximum efficiency.

---

## 💎 Key Features 💎

* 💬 **Modern Chat-Style View:** Conversations are displayed in a clean, easy-to-read chat interface for both customers and admins, complete with profile avatars.
* 🔗 **Seamless WooCommerce Integration:** Adds a "Support Tickets" tab directly to the WooCommerce **My Account page**. It feels like a core part of your store.
* 📊 **Advanced Ticket Management:** A powerful admin dashboard with at-a-glance statistics and a streamlined workflow.
* 🔍 **Intelligent Search & Filtering:** Instantly find tickets by ID, subject, status, priority, or date range.
* ✅ **Bulk Actions:** Save time by selecting and updating multiple tickets at once to close or delete them.
* 🔥 **Ticket Priority System:** Let customers set a priority (Low, Normal, High, Urgent) so you can address the most critical issues first.
* ⭐ **Customer Feedback Ratings:** Allow customers to rate their support experience with an interactive star-rating system after a ticket is closed.
* 📎 **Secure & Flexible File Attachments:** Enable file uploads for customers and admins. You control everything: max files, max size, and allowed file types.
* 💾 **Dual Attachment Storage:** Choose your preferred storage method: save space with an **external API** (like `imgbb.com`) or keep files in your local **WordPress Media Library**.
* 📧 **Customizable Email Notifications:** Professional HTML emails notify everyone of new tickets and replies. Easily customize the sender, recipients, and template style.
* 🔑 **Automatic Updates:** Deliver updates to paying customers directly from their WordPress dashboard with a secure and professional license key system.
* 🛠️ **Robust Database Management:** Includes a self-healing database checker to ensure smooth and error-free transitions between plugin versions.

---

## ⚙️ Installation & Setup ⚙️

1.  Upload the plugin folder to your `/wp-content/plugins/` directory.
2.  Activate the plugin through the **Plugins** menu in WordPress.
3.  Navigate to **Support Tickets > Settings** to configure the plugin to your liking.
4.  To enable automatic updates, go to the **Settings > Licensing** tab and enter the license key you received with your purchase.
5.  **You're all set!** A "Support Tickets" tab will now automatically appear in your customers' WooCommerce "My Account" page.

---

## 🤔 Frequently Asked Questions (FAQ)

#### My notification emails are going to spam. How do I fix this?
This is a common WordPress issue. The best solution is to use a dedicated SMTP plugin to improve email deliverability for your entire website. We recommend installing **WP Mail SMTP** and configuring it with your email provider. Our plugin is fully compatible with it.

#### How do the file attachments work?
You can enable attachments from the **Settings > General** tab. You have full control over the maximum number of files per upload, the maximum size of each file, and the allowed file extensions (e.g., `jpg`, `png`, `pdf`, `zip`).

#### Where are the attached files stored?
You have two options under **Settings > General > Attachment Storage Method**:
1.  **External:** Use an API key for a service like `imgbb.com` to upload all files externally, saving your server's storage space.
2.  **Local Storage:** Enable the "Enable Local Storage" option to upload files to your standard WordPress Media Library.

---

## 📜 Changelog 📜

<h4>Version 3.0.7</h4><ul><li>FIX: Corrected a form wrapping and overflow error on the ticket view page.</li><li>ENHANCEMENT: Improved the user interface and design of the chat system for better readability.</li><li>ENHANCEMENT: Added the official plugin banner and icon for a more professional appearance in the repository.</li></ul><h4>Version 3.0.6</h4><ul><li>FIX: Corrected an issue that caused some emails to go to the spam folder.</li><li>FIX: Resolved a PHP warning on the ticket creation page.</li><li>ENHANCEMENT: Added client-side validation and a loading spinner for file uploads.</li></ul>

#### `3.0.6`
* **ENHANCEMENT:** Added client-side validation for file attachments. The form now provides instant feedback if a file is too large or an unsupported type.
* **ENHANCEMENT:** Added a loading spinner to the submit button when files are being uploaded to provide better user feedback.
* **FIX:** Corrected a PHP parse error in the admin ticket view template.

#### `3.0.5`
* **FEATURE:** Implemented a professional, secure automatic update system using a license key and a private GitHub repository.
* **FEATURE:** Added a robust database schema checker to prompt admins to run necessary database updates after upgrading.
* **FIX:** Hardened the email sending function with more reliable headers to improve deliverability and prevent emails from going to spam.

#### `3.0.4`
* **FEATURE:** Added a flexible file attachment system for tickets and replies.
* **FEATURE:** Added comprehensive settings to control file attachments, including max files, max size, allowed types, and storage location (external API or local).
* **ENHANCEMENT:** Reorganized and cleaned up the admin settings panel for better readability and usability. Consolidated email settings into a single tab.

#### `3.0.3`
* **FEATURE:** Implemented bulk actions on the admin ticket list. Admins can now select multiple tickets to close or delete them at once.
* **FEATURE:** Added customer feedback via an interactive star-rating system on closed tickets.
* **ENHANCEMENT:** Improved the admin ticket search to intelligently handle searches by subject text or by ticket ID (when using a '#').

#### `3.0.2`
* **FEATURE:** Added a Ticket Priority system. Customers can now set a priority when creating a ticket, and admins can view and filter by it.

#### `3.0.1`
* **STABILITY:** This release focuses on ultimate stability and administrator control, resolving all known bugs and implementing the final, correct versions of all features.
* **FIX:** Permanently resolved a critical bug that prevented the plugin from being deactivated. The plugin is now fully stable and follows all WordPress best practices for dependency handling.
* **FIX:** Corrected the WooCommerce "incompatible plugin" notice by properly declaring support for High-Performance Order Storage (HPOS).
* **FIX:** Implemented a robust and reliable pagination system for the customer-facing ticket list that works correctly with all server configurations and WooCommerce endpoints.
* **IMPROVEMENT:** Replaced the buggy "Screen Options" panel with a reliable, database-driven setting in the "General" tab for controlling the number of tickets displayed per page in the admin dashboard. The "General" tab and its settings are now fully restored and functional.
* **IMPROVEMENT:** Refined all code to be compliant with WordPress coding standards, improving long-term stability and maintainability.

#### `2.2.0`
* **FEATURE:** Added a professional deactivation feedback form. When an admin deactivates the plugin, a pop-up now asks for feedback.
* **FEATURE:** Implemented a robust WooCommerce dependency check. The plugin will now refuse to activate and will display a clear error message if WooCommerce is not active.

#### `2.1.0`
* **FEATURE:** Added a secure "Delete" action link for administrators to permanently delete tickets and all associated data, with a confirmation prompt to prevent accidents.
* **FEATURE:** Implemented a detailed "Activity Log" in the admin ticket view, showing a complete audit trail of every status change and reply for full transparency.
* **FEATURE:** Added system messages directly into the chat timeline for events like "Ticket Closed".

#### `2.0.0`
* **MAJOR FEATURE:** Redesigned the ticket view with a clean, modern chat interface for a better user experience.
* **FEATURE:** Added profile pictures (avatars) to the chat view for both customers and admins.
* **FEATURE:** Added a "Cancelled" status for tickets.
* **FIX:** Resolved a critical bug in the automatic status update logic. The status now correctly and reliably shifts between Open, Processing, Awaiting Admin Reply, and Awaiting Customer Reply.
* **FIX:** Prevented users from being able to reply to tickets that are "Closed" or "Cancelled".

#### `1.1.0`
* **FIX:** Resolved critical database creation errors that occurred on plugin activation, ensuring all tables and columns are created correctly every time.
* **FIX:** Corrected an issue where the front-end ticket creation form would fail silently. The system now has proper error handling.
* **FIX:** Implemented a permalink flush on activation to ensure custom URLs (/support-tickets/) work immediately without needing manual intervention.

#### `1.0.0`
* **FEATURE:** Initial release of the WooCommerce Support Ticket System.
* **FEATURE:** Creation of custom database tables (tickets, replies, history) for high performance and data integrity.
* **FEATURE:** Customer-facing ticket creation form and ticket history list in the WooCommerce "My Account" area.
* **FEATURE:** Admin-facing dashboard for viewing and managing all tickets, with a notification bubble for new tickets.
* **FEATURE:** Core two-way communication chat system.
* **FEATURE:** Fully customizable HTML email templates with options for a custom logo, header color, and footer text.
* **FEATURE:** A tabbed settings page for easy configuration of all plugin options.
