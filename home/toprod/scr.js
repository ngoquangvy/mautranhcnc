const searchParams = new URLSearchParams(window.location.search);

const idValue = searchParams.get('id'); // price_descending
if (idValue) {
    // Create an XMLHttpRequest object

    var xhr = new XMLHttpRequest();

    // Open a request to the server
    //xhr.open("GET", "http://localhost/mautranhcnc/home/apitest.php?id=" + idValue, true);
    xhr.open("GET", "https://mautranhcnc.com/home/apitest.php?id=" + idValue, true);
    // Set a callback function to handle the response
    xhr.onload = function () {
        // Check if the request was successful
        if (xhr.status === 200) {
        
            var titleclass = document.querySelector('.titlecontent');
            var titlecontent = document.createElement("h2");
            titlecontent.textContent = "Mautranhcnc :   " + idValue;
            titleclass.appendChild(titlecontent);
            // Parse the JSON response
            var data = JSON.parse(xhr.responseText);
            // Update the UI with the data
            // Convert the array to a JSON string
            const jsonString = JSON.stringify(data);

            // Parse the JSON array
            var parsedJsonArray = JSON.parse(jsonString);
            var typeproclass = document.querySelector('.typepro');
            parsedJsonArray.forEach(function (item) {
                // Create a new `div` element
                var divElement = document.createElement("div");
                divElement.className = "column";

                // Create an anchor element
                var anchorElement = document.createElement("a");

                // Set the href to a relative URL on the same server
                anchorElement.href = "../viewimg.php?id=" + item.id + ""; // Replace with the actual path

                // Create an image element
                var imgElement = document.createElement("img");
                item.prourl = '../imgs/' + item.prourl + '';
                imgElement.src = item.prourl;
                imgElement.loading = "lazy";

                // Create a span element
                var spanElement = document.createElement("h3");
                spanElement.className = "prod-name";
                spanElement.textContent = item.proname;
		var title = document.createElement("h2");
                

                // Append the image and span to the anchor element
                anchorElement.appendChild(imgElement);
                anchorElement.appendChild(spanElement);

                // Append the anchor element to the div element
                divElement.appendChild(anchorElement);

                // Append the div element to the typepro class
                typeproclass.appendChild(divElement);
            });
            // Append the `ul` element to the DOM

            // const sliderWrapper = document.querySelector('.slider-wrapper');
            // const prevslideButton = document.querySelector('#prev-slide');
            // sliderWrapper.insertBefore(ulElement, prevslideButton.nextSibling);
        } else {
            // Handle the error
            console.log(xhr.statusText);
        }
    };

    // Send the request
    xhr.send();
    window.onload = function () {
        const typepro = document.getElementById("typepro");
        const column = document.getElementById("column");
        let isScrollPaused = false;
        typepro.addEventListener("wheel", function (event) {
            event.preventDefault();
            isScrollPaused = true;
            // Calculate the direction of the scroll
            const direction = event.deltaY > 0 ? 1 : -1;

            // Calculate the scroll distance to the next item
            const scrollDistance = typepro.clientWidth / 4;

            // Scroll to the next or previous item
            typepro.scrollLeft += direction * scrollDistance;
        });
        const nextButton = document.getElementById("nextButton");
        const prevButton = document.getElementById("prevButton");
        prevButton.style.display = "none";
        typepro.addEventListener("scroll", function () {
            // Check if at the top of the list
            prevButton.style.display = typepro.scrollLeft === 0 ? "none" : "block";
            // Check if at the end of the list
            nextButton.style.display =
                typepro.scrollLeft >= typepro.scrollWidth - typepro.clientWidth - 10
                    ? "none"
                    : "block";
        });
        nextButton.addEventListener("click", function () {
            isScrollPaused = true;
            typepro.scrollLeft += typepro.clientWidth / 10;
        });

        prevButton.addEventListener("click", function () {
            isScrollPaused = true;
            typepro.scrollLeft -= typepro.clientWidth / 10;
        });
        
        // Add swipe event listener to pause scrolling
        typepro.addEventListener('touchstart', function (e) {
            isScrollPaused = true;
        });

        // Add mouseenter event listener to pause scrolling
        typepro.addEventListener('mouseenter', function () {
            isScrollPaused = true;
        });

        // Add mouseleave event listener to continue scrolling
        typepro.addEventListener('mouseleave', function () {
            isScrollPaused = false;
            // Trigger the initial scroll
            startScroll();
        });

        // Set interval for scrolling
        // Set interval for scrolling
        // const scrollInterval = setInterval(function () {
        //     if (isScrollPaused === false) {
        //         const nextScrollLeft = typepro.scrollLeft + typepro.clientWidth / 6;
        //         const maxScrollLeft = typepro.scrollWidth - typepro.clientWidth;

        //         if (nextScrollLeft <= maxScrollLeft) {
        //             animateScroll(typepro, nextScrollLeft);
        //         } else {
        //             // If reached the end, scroll to the beginning
        //             animateScroll(typepro, 0);
        //         }
        //     }
        // }, 0.001);
        // Set interval for scrolling
        function startScroll() {
            if (isScrollPaused === false) {
            
             	let scrollDistance = typepro.clientWidth / 6; // Default scroll distance

                // Check if the screen width is less than a certain value (e.g., 600 pixels)
                if (window.innerWidth < 600) {
                    // Adjust the scroll distance for smaller screens
                    scrollDistance = typepro.clientWidth / 3; // Adjust this value as needed
                }

                const nextScrollLeft = typepro.scrollLeft + scrollDistance;
                const maxScrollLeft = typepro.scrollWidth - typepro.clientWidth;

                if (nextScrollLeft < maxScrollLeft) {
                    animateScroll(typepro, nextScrollLeft, startScroll);
                } else {
                    // If reached the end, scroll to the exact end
                    animateScroll(typepro, maxScrollLeft, startScroll);
                }
            }
        }

        // Trigger the initial scroll
        startScroll();

        // JavaScript animate function for smooth scrolling
        function animateScroll(element, to, callback) {
            const duration = 1000;
            const start = element.scrollLeft;
            const startTime = performance.now();

            function scroll() {
                const currentTime = performance.now();
                const progress = Math.min((currentTime - startTime) / duration, 1);
                element.scrollLeft = start + progress * (to - start);

                if (progress < 1 && isScrollPaused === false) {
                    requestAnimationFrame(scroll);
                } else {
                    // Call the callback function to continue scrolling
                    callback();
                }
            }

            requestAnimationFrame(scroll);
        }
    }




    // // JavaScript animate function for smooth scrolling
    // function animateScroll(element, to) {
    //     const duration = 500;
    //     const start = element.scrollLeft;
    //     const startTime = performance.now();

    //     function scroll() {
    //         const currentTime = performance.now();
    //         const progress = Math.min((currentTime - startTime) / duration, 1);
    //         element.scrollLeft = start + progress * (to - start);

    //         if (progress < 1 && isScrollPaused === false) {
    //             requestAnimationFrame(scroll);
    //         }
    //     }

    //     requestAnimationFrame(scroll);
    // }
} else {
    console.error('ID parameter not found in the URL.');
}