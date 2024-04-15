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
if (div)
  div.addEventListener("wheel", function (event) {
    event.preventDefault();
    div.scrollLeft += event.deltaY;
  });

var inputElement = document.querySelector("#ser-input");

if (inputElement)
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
// Hàm để kiểm tra chiều ngang và ẩn danh sách nếu cần
function checkScreenWidth() {
  const screenWidth = window.innerWidth || document.documentElement.clientWidth;
  const thresholdWidth = 768;
  if (document.getElementById('sidebar'))
    if (screenWidth <= thresholdWidth) {
      document.getElementById('sidebar').style.display = 'none';
    } else {
      document.getElementById('sidebar').style.display = 'block';
    }
}
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
  // Gọi hàm để ẩn danh sách khi trang được tải
  checkScreenWidth();

  // Gọi hàm để kiểm tra chiều ngang khi kích thước của cửa sổ thay đổi
  $(window).resize(function () {
    checkScreenWidth();
  });

  $(".btndel").on("click", function () {
    ta = $(this).attr('value');
    const xhttp = new XMLHttpRequest();
    xhttp.onload = function () {
      if (this.responseText && this.responseText == "deleted") {
        const element = document.getElementById(ta);
        console.log(element)
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
  $(".btndelprotype").on("click", function () {
    ta = $(this).attr('value');
    deleteparent = $(this);
    const xhttp = new XMLHttpRequest();
    xhttp.onload = function () {
      if (this.responseText && this.responseText == "deleted") {
        deleteparent.parent().hide();
      } else {
        $("#products").html("Chưa thể xóa");
      }
    }
    xhttp.open("GET", "deletept.php?t=" + ta);
    xhttp.send();
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