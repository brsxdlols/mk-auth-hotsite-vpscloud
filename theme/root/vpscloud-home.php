<?php
// Compatibility for old direct links. MK-Auth owns theme selection and rendering.
header('Cache-Control: no-store');
header('Location: /index.hhvm', true, 302);
exit;
