document.getElementById('imageUpload').addEventListener('change', function (event) {
    const files = event.target.files;

    if (!files || files.length === 0) return;

    const compressedImagesList = document.getElementById('compressedImagesList');
    const uploadButton = document.getElementById('uploadButton');

    // Đếm số lượng ảnh đã nén và hiển thị
    let processedCount = 0;

    // Xóa danh sách các ảnh đã nén trước đó (nếu có)
    // compressedImagesList.innerHTML = '';

    // Ẩn nút "Update" và hiển thị biểu tượng loading
    uploadButton.textContent = 'Processing...'; // Thay đổi văn bản của nút thành "Processing..."
    uploadButton.disabled = true; // Tạm ngưng hoạt động của nút "Update"

    // Duyệt qua từng tệp ảnh đã chọn
    Array.from(files).forEach(file => {
        const reader = new FileReader();

        reader.onload = function (e) {
            const img = new Image();

            img.onload = function () {
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');

                // Đặt kích thước canvas giống với kích thước của ảnh
                canvas.width = img.width;
                canvas.height = img.height;

                // Vẽ ảnh lên canvas
                context.drawImage(img, 0, 0);

                const targetFileSize = 500 * 1024; // Kích thước tệp mong muốn là 500 KB
                let quality = 0.9; // Chất lượng ban đầu là 90%
                const maxIterations = 10; // Số lần tối đa để điều chỉnh chất lượng

                for (let i = 0; i < maxIterations; i++) {
                    const compressedImageData = canvas.toDataURL('image/jpeg', quality);
                    const compressedFileSize = Math.round(compressedImageData.length * 0.75); // Ước tính kích thước dựa trên base64

                    if (compressedFileSize <= targetFileSize) {
                        // Tạo một phần tử <li> để chứa ảnh đã nén và thông tin kích thước
                        const listItem = document.createElement('li');

                        // Tạo một thẻ <img> để hiển thị ảnh đã nén
                        const compressedImage = document.createElement('img');
                        compressedImage.src = compressedImageData;
                        compressedImage.style.maxWidth = '200px'; // Có thể thay đổi kích thước tối đa của ảnh
                        compressedImage.textContent = "Remove";
                        // Thêm ảnh đã nén vào trong phần tử <li>
                        listItem.appendChild(compressedImage);

                        // Hiển thị dung lượng ảnh sau khi nén
                        const formattedSize = (compressedFileSize / 1024).toFixed(2); // Chuyển đổi dung lượng thành KB và làm tròn đến 2 chữ số thập phân
                        const sizeInfo = document.createElement('p');
                        sizeInfo.textContent = `Size: ${formattedSize} KB`;

                        // Thêm thông tin dung lượng vào trong phần tử <li>
                        listItem.appendChild(sizeInfo);
                        // Thêm nút xóa image
                        const removebutton = document.createElement('button');
                        removebutton.className = "remove-button";
                        removebutton.onclick = function () {
                            var listItem = this.parentNode;
                            listItem.parentElement.removeChild(listItem)
                            // Kiểm tra nếu không còn phần tử <li> nào trong danh sách
                            var imagesList = document.getElementById('compressedImagesList');
                            if (imagesList.querySelectorAll('li').length === 0) {
                                uploadButton.textContent = 'Hãy chọn ảnh'; // Thay đổi văn bản của nút thành "Update"
                                uploadButton.disabled = true; // Kích hoạt lại hoạt động của nút "Update"
                            }
                        };
                        removebutton.textContent = "x";
                        listItem.appendChild(removebutton);
                        // Thêm phần tử <li> vào danh sách đã nén
                        compressedImagesList.appendChild(listItem);

                        // Tăng biến đếm số lượng ảnh đã xử lý thành công
                        processedCount++;

                        // Kiểm tra nếu đã xử lý thành công tất cả ảnh
                        if (processedCount === files.length) {
                            // Hiển thị lại nút "Update" sau khi đã xử lý xong
                            uploadButton.textContent = 'Update'; // Thay đổi văn bản của nút thành "Update"
                            uploadButton.disabled = false; // Kích hoạt lại hoạt động của nút "Update"
                        }

                        break; // Kết thúc vòng lặp sau khi đã nén ảnh thành công
                    } else {
                        // Nếu kích thước ảnh nén vẫn lớn hơn kích thước mong muốn, giảm chất lượng và tiếp tục vòng lặp
                        const scaleFactor = Math.sqrt(targetFileSize / compressedFileSize); // Tính tỷ lệ giảm chất lượng
                        quality *= scaleFactor; // Điều chỉnh chất lượng mới
                    }
                }
            };

            img.src = e.target.result;
        };

        reader.readAsDataURL(file);
    });
});

// Xử lý sự kiện khi nhấn nút Upload Images to Server
document.getElementById('uploadButton').addEventListener('click', function () {
    const compressedImages = Array.from(document.querySelectorAll('#compressedImagesList img'));
    const compressedImagesList = document.getElementById('compressedImagesList');
    // Tạo một FormData để chứa các ảnh đã nén và các giá trị từ các trường input
    const formData = new FormData();

    compressedImages.forEach((img, index) => {
        // Chuyển đổi base64 của ảnh đã nén thành Blob
        const dataUrl = img.src;
        const blob = dataURLtoBlob(dataUrl);

        // Thêm Blob vào FormData với tên field là 'file' + số thứ tự
        formData.append(`file${index + 1}`, blob, `image_${index + 1}.jpg`);
    });

    // Lấy giá trị từ các trường input và thêm vào FormData
    const nameInput = document.querySelector('input[name="nameimg"]');
    const typeInput = document.querySelector('input[name="typeimg"]');
    const descriptionInput = document.querySelector('input[name="desimg"]');

    formData.append('nameimg', nameInput.value); // Thêm giá trị của trường 'nameimg' vào FormData
    formData.append('typeimg', typeInput.value); // Thêm giá trị của trường 'typeimg' vào FormData
    formData.append('desimg', descriptionInput.value); // Thêm giá trị của trường 'desimg' vào FormData

    // Gửi FormData lên máy chủ (ví dụ: sử dụng fetch hoặc XMLHttpRequest)
    fetch('../uploadpd.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (response.ok) {
                // Xử lý phản hồi thành công
                return response.json(); // Đọc nội dung của phản hồi dưới dạng văn bản
            } else {
                // Xử lý phản hồi lỗi
                throw new Error('Upload failed');
            }
        })
        .then(data => {
            // Xử lý dữ liệu từ phản hồi
            //console.log(data); // In dữ liệu từ phản hồi lên console
            alert('Upload successful!'); // Hiển thị thông báo upload thành công
            compressedImagesList.innerHTML = ''; // xóa ảnh sau khi update xong
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Upload failed!');
        });
});

// Hàm chuyển đổi base64 thành Blob
function dataURLtoBlob(dataUrl) {
    const arr = dataUrl.split(',');
    const mime = arr[0].match(/:(.*?);/)[1];
    const bstr = atob(arr[1]);
    let n = bstr.length;
    const u8arr = new Uint8Array(n);

    while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
    }

    return new Blob([u8arr], { type: mime });
}
