// ------ HORIZONTAL SCROLL & GRAB LOGIC ------
const scrollContainer = document.querySelector("#typepro");

if (scrollContainer) {
  // Wheel Scroll (Vertical to Horizontal)
  scrollContainer.addEventListener("wheel", (evt) => {
    evt.preventDefault();
    scrollContainer.scrollLeft += evt.deltaY;
  });

  // Grab to Scroll (Desktop Drag)
  let isDown = false;
  let startX;
  let scrollLeft;

  scrollContainer.addEventListener("mousedown", (e) => {
    isDown = true;
    scrollContainer.classList.add("active");
    startX = e.pageX - scrollContainer.offsetLeft;
    scrollLeft = scrollContainer.scrollLeft;
  });
  scrollContainer.addEventListener("mouseleave", () => {
    isDown = false;
    scrollContainer.classList.remove("active");
  });
  scrollContainer.addEventListener("mouseup", () => {
    isDown = false;
    scrollContainer.classList.remove("active");
  });
  scrollContainer.addEventListener("mousemove", (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - scrollContainer.offsetLeft;
    const walk = (x - startX) * 2; // scroll-fast factor
    scrollContainer.scrollLeft = scrollLeft - walk;
  });
}

function right() {
    if (scrollContainer) scrollContainer.scrollBy({ left: 200, behavior: 'smooth' });
}
function left() {
    if (scrollContainer) scrollContainer.scrollBy({ left: -200, behavior: 'smooth' });
}

var inputElement = document.querySelector("#ser-input");

if (inputElement)
  document.querySelector("#ser-input").addEventListener("keyup", function (event) {
    if (event.keyCode === 13) {
      event.preventDefault();
      document.querySelector("form").submit();
    }
  });

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

// ------ MULTI-DELETE UNDO LOGIC (Improved) ------
let undoStack = []; // [{ id, type: 'product'|'category', element, timer, undoAction, deleteAction }]

function updateUndoToast() {
  let toast = document.getElementById('undo-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'undo-toast';
    toast.innerHTML = `<span id="toast-msg"></span><button id="undo-btn">Hoàn tác</button><div class="toast-progress"></div>`;
    document.body.appendChild(toast);
    
    document.getElementById('undo-btn').onclick = function() {
      if (undoStack.length > 0) {
        const item = undoStack.pop();
        if (item.timer) clearTimeout(item.timer);
        if (item.undoAction) item.undoAction();
        updateUndoToast();
      }
    };
  }

  const msgSpan = document.getElementById('toast-msg');
  if (undoStack.length > 0) {
    msgSpan.textContent = `Đã xóa ${undoStack.length} mục.`;
    toast.classList.add('show');
  } else {
    toast.classList.remove('show');
  }
}

function addToUndoStack(id, type, element, undoAction, deleteAction) {
  const item = {
    id: id,
    type: type,
    element: element,
    undoAction: undoAction,
    deleteAction: deleteAction,
    timer: null
  };

  item.timer = setTimeout(() => {
    // Remove from stack silently before deleting
    undoStack = undoStack.filter(v => v.id !== id);
    if (deleteAction) deleteAction();
    updateUndoToast();
  }, 5000);

  undoStack.push(item);
  updateUndoToast();
}

$(document).ready(function () {
  checkScreenWidth();

  $(window).resize(function () {
    checkScreenWidth();
  });

  // Product Delete with Multi-Undo
  $(".btndel").on("click", function () {
    const val = $(this).attr('value');
    const element = document.getElementById(val);
    if (!element || undoStack.find(item => item.id === val)) return;

    // Visual hide
    $(element).css('opacity', '0.2').css('pointer-events', 'none');
    
    addToUndoStack(val, 'product', element,
      function() { // Undo
        $(element).css('opacity', '1').css('pointer-events', 'auto');
      }, 
      function() { // Confirm Delete
        const xhttp = new XMLHttpRequest();
        xhttp.onload = function () {
          if (this.responseText && this.responseText == "deleted") {
            element.remove();
          } else {
            $(element).css('opacity', '1').css('pointer-events', 'auto');
            alert("Lỗi: Không thể xóa sản phẩm " + val);
          }
        }
        xhttp.open("GET", "deletepd.php?t=" + val);
        xhttp.send();
      }
    );
  });

  // Category Delete with Multi-Undo
  $(".btndelprotype").on("click", function (e) {
    e.preventDefault();
    e.stopPropagation(); // Critical: Stop sidebar link from triggering

    const val = $(this).attr('value');
    if (undoStack.find(item => item.id === val)) return;

    // Visual hide/disable
    const isSidebar = $(this).closest('.sidebar-category-item').length > 0;
    const targets = [];
    
    if (isSidebar) {
      const parentLi = $(this).closest('.sidebar-category-item');
      parentLi.hide();
      targets.push(parentLi);
    } else {
      // Header button click - we might want to hide the products grid or similar
      $(this).prop('disabled', true).text('Đang xóa...');
      const grid = $('#products');
      grid.css('opacity', '0.2').css('pointer-events', 'none');
      targets.push($(this), grid);
    }
    
    addToUndoStack(val, 'category', targets,
      function() { // Undo
        if (isSidebar) {
          targets[0].show();
        } else {
          targets[0].prop('disabled', false).html('<i class="fa fa-trash mr-2"></i> Xóa danh mục');
          targets[1].css('opacity', '1').css('pointer-events', 'auto');
        }
      }, 
      function() { // Confirm Delete
        const xhttp = new XMLHttpRequest();
        xhttp.onload = function () {
          if (this.responseText && this.responseText == "deleted") {
            if (isSidebar) {
              targets[0].remove();
            } else {
              window.location.href = 'admin.php'; // Redirect after deleting current category
            }
          } else {
            // Restore on error
            if (isSidebar) {
              targets[0].show();
            } else {
              targets[0].prop('disabled', false).html('<i class="fa fa-trash-o mr-2"></i> Xóa danh mục');
              targets[1].css('opacity', '1').css('pointer-events', 'auto');
            }
            alert("Lỗi: Không thể xóa danh mục " + val);
          }
        };
        xhttp.open("GET", "deletept.php?t=" + val);
        xhttp.send();
      }
    );
  });

  $(".typepro").click(function () {
    if (document.getElementById("btnhide")) document.getElementById("btnhide").click();
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
  });

  $(".contactt").click(function () {
    if (document.getElementById("btnhide")) document.getElementById("btnhide").click();
  });
});