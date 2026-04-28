<?php
/**
 * js_escape.php — Helper for safely embedding PHP values inside <script> blocks.
 *
 * Use INSTEAD of htmlspecialchars() or addslashes() when injecting PHP into JS.
 *
 * Usage:
 *     <script>
 *       const TOKEN  = <?= js_value($token) ?>;
 *       const FOLIO  = <?= js_value($folio) ?>;
 *       const USER   = <?= js_value($_SESSION['user_name'] ?? '') ?>;
 *     </script>
 *
 * Notes:
 *   - js_value() outputs a JSON literal (string, number, bool, null, array, object)
 *     so DO NOT add surrounding quotes in your template — json_encode emits them
 *     when needed for strings.
 *   - The flags HEX_TAG/AMP/APOS/QUOT make the output safe to embed inside
 *     HTML attributes too, and prevent breaking out of <script> via "</script>"
 *     in user data.
 */

declare(strict_types=1);

if (!function_exists('js_value')) {
    /**
     * Encode a PHP value as a JavaScript literal that is also safe inside HTML.
     */
    function js_value($value): string {
        return json_encode(
            $value,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }
}
