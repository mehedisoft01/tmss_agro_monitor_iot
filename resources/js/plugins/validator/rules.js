import { defineRule } from 'vee-validate';

// Required field
// Usage: v-validate="'required'"
defineRule("required", (value) => (value ? true : "This field is required"));

// Email format validator
// Usage: v-validate="'required|email'"
defineRule("email", (value) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(value) || "This must be a valid email";
});

// Minimum length
// Usage: v-validate="'required|min:6'"
defineRule("min", (value, [minLength]) => {
    if (!minLength) return "Minimum length parameter is missing";
    return value.length >= minLength ? true : `Minimum length is ${minLength}`;
});

// Maximum length
// Usage: v-validate="'required|max:12'"
defineRule("max", (value, [maxLength]) => {
    if (!maxLength) return "Maximum length parameter is missing";
    return value.length <= maxLength ? true : `Maximum length is ${maxLength}`;
});

// Only numbers allowed
// Usage: v-validate="'required|numeric'"
defineRule("numeric", (value) => {
    return /^[0-9]+$/.test(value) || "This field must be numeric";
});

// Match regex pattern
// Usage: v-validate="'regex:^\\d{4}-\\d{2}-\\d{2}$'"  (YYYY-MM-DD format)
defineRule("regex", (value, [pattern]) => {
    if (!pattern) return "Regex pattern is missing";
    const regex = new RegExp(pattern);
    return regex.test(value) || "Invalid format";
});

// Confirmed (match another field, e.g. password confirmation)
// Usage: v-validate="'required|confirmed:password'"
defineRule("confirmed", (value, [target], ctx) => {
    if (!target) return "Confirmation target is missing";
    return value === ctx.form[target] || "Values do not match";
});

// Value length between two numbers
// Usage: v-validate="'between:3,8'"
defineRule("between", (value, [min, max]) => {
    if (!min || !max) return "Between rule requires min and max";
    return (
        (value.length >= min && value.length <= max) ||
        `Length must be between ${min} and ${max}`
    );
});

// Positive number only
// Usage: v-validate="'positive'"
defineRule("positive", (value) => {
    return Number(value) > 0 || "Value must be positive";
});

// Must be a valid URL
// Usage: v-validate="'url'"
defineRule("url", (value) => {
    try {
        new URL(value);
        return true;
    } catch {
        return "This must be a valid URL";
    }
});
