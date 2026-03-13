# GD License Manager — Reseller Plan & Billing Add-On
## Complete Integration Guide

---

## 📁 Files in This Package

```
plan_addon_db.sql              ← Run this SQL FIRST
plans.php                      ← Admin: Manage reseller plans
payments.php                   ← Admin: View/approve/reject payments
reseller_plan.php              ← Reseller: My Plan dashboard
setting.php                    ← Replace your existing setting.php (adds payment config tabs)
api/plan_api.php               ← API for plan CRUD + assign
api/payment_api.php            ← API for bank transfer & Razorpay
api/settings_api.php           ← Replace/merge with your existing settings_api.php
sections/sidebar.php           ← Replace your existing sidebar.php (adds new nav items)
include/quota_check.php        ← Helper: quota enforcement for license creation
modals/plan_modal.php          ← Modal: Create new plan
modals/edit_plan_modal.php     ← Modal: Edit existing plan
modals/assign_plan_modal.php   ← Modal: Manually assign plan to reseller
modals/buy_extra_licenses_modal.php  ← Modal: Reseller buys extra licenses
modals/upgrade_plan_modal.php  ← Modal: Reseller upgrades/subscribes plan
modals/view_invoice_modal.php  ← Modal: Invoice viewer + print
modals/approve_payment_modal.php     ← Modal: Admin approves/rejects payment
modals/my_invoices_modal.php   ← Modal: Reseller's invoice list
```

---

## 🚀 Step 1: Run the SQL

Open your phpMyAdmin or MySQL console and run:
```
plan_addon_db.sql
```
This creates:
- `reseller_plans` table
- `payments` table
- Adds `plan_id`, `plan_expiry`, `extra_licenses` columns to `users` table
- Inserts default billing config into `app_config`
- Creates 3 sample plans (Starter, Growth, Pro)

---

## 🚀 Step 2: Copy Files

Copy ALL files into your project root matching the same folder structure:

```
your-project/
├── plans.php                    ← NEW
├── payments.php                 ← NEW
├── reseller_plan.php            ← NEW
├── setting.php                  ← REPLACE
├── api/
│   ├── plan_api.php             ← NEW
│   ├── payment_api.php          ← NEW
│   └── settings_api.php        ← REPLACE (or merge your old one)
├── sections/
│   └── sidebar.php              ← REPLACE
├── include/
│   └── quota_check.php         ← NEW
└── modals/
    ├── plan_modal.php
    ├── edit_plan_modal.php
    ├── assign_plan_modal.php
    ├── buy_extra_licenses_modal.php
    ├── upgrade_plan_modal.php
    ├── view_invoice_modal.php
    ├── approve_payment_modal.php
    └── my_invoices_modal.php
```

---

## 🚀 Step 3: Enforce Quota in License Creation

Open your existing `api/license_api.php`. Find where a license is created (the `save_license` action). Add quota enforcement at the top of that block:

```php
if ($action === 'save_license') {
    // ── ADD THESE 5 LINES ──────────────────────────────────────
    if ($my_role === 'reseller') {
        require_once __DIR__ . '/../include/quota_check.php';
        $quota = check_reseller_quota($conn, $my_id);
        if (!$quota['allowed']) {
            echo json_encode(['status' => 'error', 'message' => $quota['message']]); exit;
        }
    }
    // ────────────────────────────────────────────────────────────
    
    // ... rest of your existing license creation code ...
}
```

---

## 🚀 Step 4: Add "Assign Plan" Button to Resellers Page

In your existing `resellers.php`, find the action buttons in the table (where edit/delete buttons are). Add this button:

```php
<button onclick="openModal('Assign Plan', 'modals/assign_plan_modal.php?id=<?php echo $row['id']; ?>')"
        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-green-50 hover:text-green-600 transition-all" title="Assign Plan">
    <i class="fas fa-id-badge text-xs"></i>
</button>
```

You can also show the reseller's plan status in the resellers table by adding a column:

```php
// In the resellers query, add a JOIN:
// LEFT JOIN reseller_plans rp ON r.plan_id = rp.id
// Then in the table row:
<td class="px-6 py-4">
    <?php if ($row['plan_name']): ?>
        <span class="px-2 py-0.5 bg-green-50 text-green-600 text-[10px] font-bold rounded-md">
            <?= htmlspecialchars($row['plan_name']) ?>
        </span>
        <div class="text-[10px] text-gray-400 mt-0.5">Exp: <?= $row['plan_expiry'] ?></div>
    <?php else: ?>
        <span class="text-[10px] text-gray-400 italic">No plan</span>
    <?php endif; ?>
</td>
```

---

## ⚙️ Step 5: Configure Payment Settings

1. Go to **Settings** → **Payment** tab
   - Enable Bank Transfer
   - Fill in your bank account details (name, account number, IFSC/routing)

2. Go to **Settings** → **Razorpay** tab (optional)
   - Enable Razorpay
   - Enter your Key ID and Secret from [Razorpay Dashboard](https://dashboard.razorpay.com/app/keys)

---

## 🔄 How It Works — Complete Flow

### Admin Side:
1. **Create Plans** → `plans.php` — Set name, license count, validity, price, extra license price
2. **Assign Plan to Reseller** → Resellers page → click plan icon → assign plan (free/manual)
3. **Review Payments** → `payments.php` — See pending bank transfers, approve to activate plans
4. **Revenue tracking** — Total revenue, pending count visible on payments page

### Reseller Side:
1. **See My Plan** → `reseller_plan.php` — Current quota, usage bar, expiry countdown
2. **Buy Extra Licenses** → Pick quantity, choose Bank Transfer or Razorpay, submit
3. **Upgrade Plan** → Choose higher plan, pay via bank or Razorpay
4. **View Invoices** → Printable invoice for every payment

### Payment Methods:
- **Bank Transfer** → Reseller transfers money, enters UTR/ref number → Admin reviews & approves manually → Plan activates
- **Razorpay** → Reseller pays online → Signature verified server-side → Plan activates INSTANTLY (no admin needed)

---

## 🔒 Security Notes

- All Razorpay payment signatures are verified server-side using HMAC SHA-256
- Razorpay secret key is never sent to the browser
- All SQL uses prepared statements
- Role-based access: resellers can only see their own payments/invoices
- Admin can only manage resellers under their own parent_id

---

## 📊 Database Schema Summary

### `reseller_plans`
| Column | Type | Description |
|--------|------|-------------|
| id | int | Auto-increment PK |
| plan_name | varchar | Plan display name |
| license_limit | int | Base license count |
| validity_days | int | Plan duration |
| price | decimal | Plan price |
| extra_license_price | decimal | Per-extra-license price |
| description | text | Plan description |
| status | enum | active / inactive |

### `payments`
| Column | Type | Description |
|--------|------|-------------|
| id | int | Auto-increment PK |
| invoice_no | varchar | Unique invoice number |
| reseller_id | int | FK to users |
| payment_type | enum | plan_purchase / plan_upgrade / extra_licenses |
| plan_id | int | FK to reseller_plans |
| extra_qty | int | Number of extra licenses |
| amount | decimal | Total amount |
| payment_method | enum | bank_transfer / razorpay |
| payment_status | enum | pending / approved / rejected / paid |
| razorpay_payment_id | varchar | Razorpay payment ID |
| bank_ref | varchar | Bank UTR/reference |
| approved_by | int | FK to users (admin) |

### New columns in `users`
| Column | Description |
|--------|-------------|
| plan_id | FK to reseller_plans (nullable) |
| plan_expiry | DATE when plan expires |
| extra_licenses | Count of extra purchased licenses |

---

## ✅ Feature Checklist

- [x] Admin creates unlimited plans with custom pricing
- [x] Per-plan extra license pricing (admin configurable)
- [x] Reseller plan dashboard with usage progress bar
- [x] Reseller can buy extra licenses (bank or Razorpay)
- [x] Reseller can upgrade plan (bank or Razorpay)
- [x] Bank transfer with UTR verification flow
- [x] Razorpay online payment with instant activation
- [x] Admin payment approval/rejection with notes
- [x] Auto plan activation on payment approval
- [x] Printable invoices with unique invoice numbers
- [x] License quota enforcement on creation
- [x] Plan expiry tracking with sidebar badges
- [x] Low quota warnings for resellers
- [x] Revenue dashboard on payments page
- [x] Pending payment badge in sidebar (admin)
- [x] Plan expiry badge in sidebar (reseller)
- [x] Manual plan assignment by admin (override payments)

---

*Built to match GD License Manager code style — same Tailwind classes, same AJAX pattern, same modal system.*
