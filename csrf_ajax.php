<?php
/**
 * csrf_ajax.php — Inyecta el token CSRF en todas las peticiones AJAX.
 *
 * `auth_check_api.php` EXIGE `X-CSRF-Token` (o `csrf_token` en el body) en
 * POST/PUT/PATCH/DELETE y responde 403 `csrf_invalid` si falta. Antes ningún
 * front lo enviaba, así que las escrituras por AJAX (asignar/actualizar
 * tickets) fallaban siempre.
 *
 * Uso: incluir DESPUÉS de auth_check.php y de cargar jQuery, dentro del HTML:
 *     <?php require __DIR__ . '/csrf_ajax.php'; ?>
 *
 * Cubre jQuery ($.ajax/$.post) y fetch() nativo del mismo origen.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<script>
(function () {
  var CSRF = <?= json_encode($_SESSION['csrf_token'], JSON_UNESCAPED_SLASHES) ?>;
  var MUTA = /^(POST|PUT|PATCH|DELETE)$/i;

  // jQuery: agrega el header a toda petición que cambie estado.
  if (window.jQuery) {
    jQuery.ajaxSetup({
      beforeSend: function (xhr, settings) {
        if (MUTA.test(settings.type || settings.method || 'GET')) {
          xhr.setRequestHeader('X-CSRF-Token', CSRF);
        }
      }
    });
  }

  // fetch(): solo para URLs del mismo origen (no toca llamadas externas).
  var _fetch = window.fetch;
  if (_fetch) {
    window.fetch = function (input, init) {
      init = init || {};
      var url = typeof input === 'string' ? input : (input && input.url) || '';
      var mismoOrigen = url.indexOf('http') !== 0 || url.indexOf(window.location.origin) === 0;
      if (mismoOrigen && MUTA.test(init.method || 'GET')) {
        var h = new Headers(init.headers || (typeof input === 'object' && input.headers) || {});
        if (!h.has('X-CSRF-Token')) { h.set('X-CSRF-Token', CSRF); }
        init.headers = h;
      }
      return _fetch.call(this, input, init);
    };
  }

  window.BACROS_CSRF = CSRF; // por si algún script lo necesita explícito
})();
</script>
