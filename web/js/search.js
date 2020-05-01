"use strict";

document.addEventListener("DOMContentLoaded", function () {
    if(null !== document.querySelector("form.search")) {
        document.querySelector("form.search").addEventListener("submit", function (e) {
            // disable standard form submit when JS is enabled for the search box
            e.preventDefault();
        });

        document.querySelector("form.search input").addEventListener("keyup", function () {
            var search = this.value.toUpperCase();
            var instituteList = document.querySelectorAll("form.searchList li button");
            var visibleCount = 0;
            instituteList.forEach(function(entry) {
                var searchIn = entry.innerHTML + " " + entry.dataset.keywords + " " + entry.value;
                if(searchIn.toUpperCase().indexOf(search) !== -1) {
                    entry.parentElement.style.display = "block";
                    visibleCount++;
                } else {
                    entry.parentElement.style.display = "none";
                }
            });
            if(0 === visibleCount) {
                document.querySelector("div.noResults").style.display = "block";
            } else {
                document.querySelector("div.noResults").style.display = "none";
            }
        });
    }
});
