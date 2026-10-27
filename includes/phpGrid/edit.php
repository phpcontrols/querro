<?php
// phpGrid Lite: grids can show edit forms (enable_edit), but saving changes needs the
// full version. The PHPGRID_ERROR prefix makes the grid show this message to the user.
http_response_code(501);
exit('PHPGRID_ERROR: Saving changes is available in the full version of phpGrid. See https://phpgrid.com/');
