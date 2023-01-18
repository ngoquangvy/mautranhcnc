
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
function deleteproduct(pro_id) {
  var ta = document.getElementById(pro_id + "abc").value;
  alert(ta)
  $.get("deletepd.php", { t: ta }, function (data) {
    // alert(data)
    if (data == "Deleted!") {
      var element = document.getElementById(pro_id);
      element.remove();
    }
  })
}
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
  // function deleteproduct(pro_id) {
  //   ta = $(this).attr('value');
  //   $.get("deletepd.php", { t: ta }, function (data) {
  //     var x = document.getElementById(pro_id);
  //     if (data == "DELETED!")
  //       x.remove();
  //   })
  // }
  // $(".btndel").on("click", function () {
  //   ta = $(this).attr('value');
  //   $.get("deletepd.php", { t: ta }, function (data) {
  //     $("#products").html(data);
  //   })
  // })
  // $(".btndel").on("click", function () {
  //   ta = $(this).attr('value');
  //   $.get("deletepd.php", { t: ta }, function (data) {
  //     var x = document.getElementById("myDIV");
  //     display("inner2.id = " + inner2.id);
  //     display("inner2.parentNode.id = " + inner2.parentNode.id);
  //     display("inner2.parentNode.parentNode.id = " + inner2.parentNode.parentNode.id);
  //     if (x.style.display === "none") {
  //       x.style.display = "block";
  //     } else {
  //       x.style.display = "none";
  //     }
  //   })
  // })
  // $(".btndel").on("click", function () {
  //   ta = $(this).attr('value');
  //   $.get("deletepd.php", { t: ta }, function (data) {
  //     $("#products").html(data);
  //   })
  // })

  // $(".btndelProtype").on("click", function () {
  //   ta = $(this).attr('value');
  //   $.get("deleteptype.php", { t: ta }, function (data) {
  //     var x = document.getElementById("myDIV");
  //     if (x.style.display === "none") {
  //       x.style.display = "block";
  //     } else {
  //       x.style.display = "none";
  //     }
  //   })
  // })
  document.getElementById("tktyc").addEventListener("click", function () {
    alert("Vui lòng liên hệ SĐT hoặc zalo, Facebook. xin cám ơn!")
  });
  document.getElementById("contact").addEventListener("click", function () {
    window.scrollTo(0, document.body.scrollHeight);
  });

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


