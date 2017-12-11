; <?php return 1; ?>
; the line above is to prevent
; viewing this file from web.
; DON'T REMOVE IT!

; This file defines the rotation object for default maps. This
; can be removed when you are not interested in demo maps rotation.

; This file also defines a backend instance of the Test backend
; which is used by some demo maps. This can be removed when
; you are not interested in the demo maps.


[rotation_demo]
maps="demo-germany,demo-ham-racks,demo-load,demo-muc-srv1,demo-geomap,demo-automap"
interval=15

[backend_demo]
backendtype=Test
