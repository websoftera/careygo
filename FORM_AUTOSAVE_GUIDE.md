# Booking Form Auto-Save Feature

## Overview

The new booking form now automatically saves your progress as you fill it out. If you refresh the page or your browser crashes, your data will be restored automatically.

---

## How It Works

### 🔄 Auto-Save Behavior

**Automatic Saving:**
- Form data is saved every **5 seconds** automatically
- Data is also saved when you:
  - Close the browser tab or window
  - Navigate away from the page
  - Switch browser tabs (visibility change)
  - Refresh the page

**Data Storage:**
- Form data is stored in your browser's **localStorage**
- Data persists for **24 hours** from when it was last saved
- After 24 hours, the draft is automatically deleted
- Draft is cleared after successful booking submission

---

## Features

### ✅ Automatic Draft Recovery

When you return to the form, you'll see:
1. **Draft Indicator** — Green banner at the top saying "Draft recovered: Your previous form data has been restored."
2. **Pre-filled Fields** — All form fields are automatically restored to their previous values
3. **Same Step** — You'll always start at Step 1, but all your previously entered data is there

### 🔘 Clear Form Button

A red **"Clear Form"** button appears in the top-right corner:
- Click it to delete the saved draft
- Asks for confirmation before clearing
- Useful if you want to start a completely fresh booking

### 🔒 Data Privacy

- Data is stored **locally in your browser only** — never sent to any server
- No one else on a shared computer can access your data (private/incognito mode)
- Clearing browser localStorage/cache will delete drafts

---

## What Gets Saved

### Pickup Address
- ✓ Pincode, Name, Phone
- ✓ Address lines 1 & 2
- ✓ City, State

### Delivery Address
- ✓ Pincode, Name, Phone, Email
- ✓ Address lines 1 & 2
- ✓ City, State

### Shipment Details
- ✓ Weight and unit (kg/gm)
- ✓ Number of pieces
- ✓ Declared value
- ✓ Description
- ✓ Customer reference

### Service Selection
- ✓ Selected service type (Standard/Premium/Air/Surface)
- ✓ Price and TAT

### Additional Options
- ✓ E-waybill preference and number
- ✓ Packing material selection
- ✓ GST invoice preference
- ✓ GSTIN and PAN numbers
- ✓ Payment method

---

## Usage Examples

### Example 1: Interrupted Booking

**Scenario:** You're filling the booking form, reach Step 5 (Summary), and your internet disconnects.

**What happens:**
1. Your form data was being saved every 5 seconds
2. When you reconnect and refresh the page, a green banner appears
3. All your data is restored
4. You can continue from Step 1 and navigate to where you left off

### Example 2: Tab Closed Accidentally

**Scenario:** You accidentally close the booking tab while filling the form.

**What happens:**
1. When the page closes, the data is saved
2. Open the booking form again
3. Your previous session's data is automatically restored
4. Continue your booking

### Example 3: Browser Crash

**Scenario:** Your browser crashes while you're at Step 4.

**What happens:**
1. The auto-save (every 5 seconds) ensures recent data is saved
2. After restarting the browser and opening the booking form
3. Data is recovered and restored
4. You can continue filling the form

### Example 4: Starting Over

**Scenario:** You want to clear everything and start fresh.

**What happens:**
1. Click the red "Clear Form" button in the top-right corner
2. Confirm when asked
3. Page reloads with all empty fields
4. No draft indicator appears
5. Start a completely fresh booking

---

## Technical Details

### Browser Support

Works on all modern browsers:
- ✓ Chrome/Chromium
- ✓ Firefox
- ✓ Safari
- ✓ Edge
- ✓ Opera

### Storage Limit

- localStorage has **~5-10 MB** available per domain
- Booking form uses **<1 KB** typically
- No issues with storage limits

### Automatic Cleanup

Drafts are automatically deleted:
- 24 hours after last save
- On successful booking completion
- When user clicks "Clear Form"
- When browser clears localStorage/site data

### Data Format

Stored as JSON in localStorage key: `careygo_booking_draft`

Example:
```json
{
  "pickup": {
    "pincode": "110001",
    "name": "John Doe",
    "phone": "9876543210",
    "addr1": "123 Main Street",
    "city": "Delhi",
    "state": "Delhi"
  },
  "delivery": {
    "pincode": "400001",
    "name": "Jane Smith",
    ...
  },
  "weight": 0.5,
  "pieces": 1,
  "_timestamp": 1705062345000
}
```

---

## Troubleshooting

### Form data not appearing after refresh?

**Possible causes:**
1. **Draft expired** — Older than 24 hours (automatically cleared)
2. **Browser cleared data** — Check if you cleared cache/cookies
3. **Private/Incognito mode** — localStorage doesn't work in private mode
4. **Multiple browsers** — Draft is separate per browser

**Solution:** Use regular mode (not incognito) to save drafts

### Draft showing but form fields empty?

**Cause:** Browser may have cached old page version

**Solution:**
1. Hard refresh: `Ctrl+Shift+R` (Windows) or `Cmd+Shift+R` (Mac)
2. Clear browser cache
3. Close tab and reopen booking page

### Need to delete old draft?

1. Click red **"Clear Form"** button in top-right
2. Or manually clear browser localStorage:
   - Open Developer Tools (F12)
   - Go to "Application" tab
   - Click "Local Storage" 
   - Find `careygo_booking_draft`
   - Delete it

---

## Security Notes

⚠️ **Important:**
- Draft is stored **locally** — only you can see it on this device
- If someone else uses your computer, they can see the draft
- Use **private/incognito mode** on shared computers
- **Clear the draft** before returning a shared device
- Never include sensitive data (passwords, card numbers) in optional fields

---

## Summary

| Feature | Details |
|---------|---------|
| **Auto-save** | Every 5 seconds |
| **Storage** | Browser localStorage (local only) |
| **Expiry** | 24 hours from last edit |
| **Clear option** | Red button in top-right |
| **After booking** | Draft is automatically cleared |
| **Private mode** | No draft saved in incognito |

Your booking form is now much more user-friendly! 🎉
