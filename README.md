# Order Follow-Up Email Module for AeroCommerce

## Overview

The **Order Follow-Up Email Module** automatically sends follow-up emails to customers based on their order history. This module triggers emails using AeroCommerce’s built-in notification system, allowing store owners to configure email templates directly in the admin panel.

### Features

- Sends a **first follow-up email** after a configurable number of days.
- Sends a **second follow-up email** if the first was sent and a specific SKU has **not** been purchased.
- Uses **Aero’s managed events**, allowing emails to be fully customizable in the admin panel.
- Configurable **SKU matching rules** for targeting specific products.
- **Queueable jobs** to ensure efficient email processing.

## Installation

### Step 1: Install the Package

Require the package via Composer:

```sh
composer require techquity/aero-order-follow-up
```

### Step 2: Configure Aero Email Notifications

Go to **Aero Admin → Configuration → Mail Notifications**, then:

1. **Create a new email notification for the first follow-up**

   - **Event:** `FirstOrderFollowUp`
   - **Recipient:** `Customer`
   - **Template:** Customize the first follow-up email.

2. **Create a new email notification for the second follow-up**

   - **Event:** `SecondOrderFollowUp`
   - **Recipient:** `Customer`
   - **Template:** Customize the second follow-up email.

## Configuration

The module settings can be managed in **Aero Admin → Settings → Order Follow-Up**:

| Setting                        | Description                                               | Default         |
| ------------------------------ | --------------------------------------------------------- | --------------- |
| `enabled`                      | Enable/Disable follow-up emails                           | `true`          |
| `first-email-item-skus-query`  | Comma-separated SKUs to check for first email             | `-SAMPLE,-FULL` |
| `second-email-item-skus-query` | Comma-separated SKUs to check before sending second email | `-BOX`          |
| `first-email-wait-time`        | Days before sending first email                           | `7`             |
| `second-email-wait-time`       | Days before sending second email                          | `21`            |
| `queue`                        | Queue name for processing emails                          | `default`       |
| `send-emails-cron`             | Cron schedule for sending emails                          | `0 9 * * *`     |

## How It Works

1. The **First Follow-Up Email** is triggered after X days if an order contains products matching `first-email-item-skus-query`.
2. The **Second Follow-Up Email** is triggered after Y days **only if**:
   - The **first email was sent**.
   - No subsequent order was placed containing `second-email-item-skus-query`.
3. Emails are dispatched using Aero’s event system, making them configurable in the admin panel.

## Events Used

The module dispatches the following Aero events:

- `FirstOrderFollowUp` → Used for the first follow-up email.
- `SecondOrderFollowUp` → Used for the second follow-up email.

---

✅ **Now your store can automatically follow up with customers and boost conversions!** 🚀
