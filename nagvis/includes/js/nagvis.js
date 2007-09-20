if(parseInt(navigator.appVersion.substring(0,1))>=3) {
 doton = new Image(13,14);
 doton.src = "images/internal/orangedot.gif";
 dotoff = new Image(13,14);
 dotoff.src = "images/internal/greendot.gif";
}

function switchdot(name,on) {
 if(parseInt(navigator.appVersion.substring(0,1))>=3){
  image = eval("" + (on == 1 ? "doton.src" : "dotoff.src"));
  document[name].src=image;
 }
}
  
function printPos($offset_x,$offset_y) {
 var Pos = "x: " + (event.clientX-$offset_x) + " / y:" + (event.clientY-$offset_y);
 window.status = Pos;
 return true;
}
