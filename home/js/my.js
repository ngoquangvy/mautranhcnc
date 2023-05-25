
var x = 0;
function right() {

  if (x <= 0) {
    x = x - 150;
  } else { x = 0; }
  document.getElementById("typeprochild").style.transform = `translateX(${x}px)`;
}
function left() {

  if (x <= 0) {
    x = x + 150;
  } else { x = 0; }
  document.getElementById("typeprochild").style.transform = `translateX(${x}px)`;
}

var div = document.getElementById("typeprochild");

div.addEventListener("wheel", function(event) {
  event.preventDefault();
  div.scrollLeft += event.deltaY;
});


document.querySelector("#ser-input").addEventListener("keyup", function (event) {
  if (event.keyCode === 13) {
    event.preventDefault();
    document.querySelector("form").submit();
  }
});
// function deleteproduct(delValue) {
//   const element = document.getElementById(delValue);
//   element.remove();
// }
var navbar = document.getElementById("navbar");
$(document).ready(function () {
  // $(".type").click(function(){
  //     ta=$(this).attr('value');
  //     $.get("protype.php",{t:ta}, function(data){
  //       window.location.href="/protype.php";
  //     })
  // })
  // var nar= document.getElementsByClassName("typepro");
  // nar[0].addEventListener('wheel',funcnar);
  // console.log(nar);
  // function funcnar(event){
  //     console.log("s");
  // }


  $(".btndel").on("click", function () {
    ta = $(this).attr('value');
    const xhttp = new XMLHttpRequest();
    xhttp.onload = function () {
      if (this.responseText && this.responseText == "deleted") {
        const element = document.getElementById(ta);
        element.remove();
      } else {
        $("#products").html("Chưa thể xóa");
      }
    }
    xhttp.open("GET", "deletepd.php?t=" + ta);
    xhttp.send();
    // $.get("deletepd.php", { t: ta }, function (data) {
    //   if (data && data == "deleted") {
    //     const element = document.getElementById(ta);
    //     element.remove();
    //   } else {
    //     $("#products").html("Chưa thể xóa");
    //   }
    // })
  })
  // document.getElementById("tktyc").addEventListener("click", function () {
  //   alert("Vui lòng liên hệ SĐT hoặc zalo, Facebook. xin cám ơn!")
  // });
  // document.getElementById("contact").addEventListener("click", function () {
  //   window.scrollTo(0, document.body.scrollHeight);
  // });

  $(".typepro").click(function () {

    document.getElementById("btnhide").click();
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;

  });
  $(".contactt").click(function () {

    document.getElementById("btnhide").click();

  });
  //   $('.col-sm-3 img').on('load', function() {
  //     $(this).addClass('show');
  // });

})


