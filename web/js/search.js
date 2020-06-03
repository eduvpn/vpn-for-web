"use strict";

document.addEventListener("DOMContentLoaded", function () {
    var preventDefault = function(e) {
        e.preventDefault();
    };

    if(null !== document.querySelector("form.search")) {
        document.querySelector("form.search").addEventListener("submit", preventDefault);

        document.querySelector("form.search input").addEventListener("keyup", function () {
            var search = this.value.toUpperCase();
            var serverList = document.querySelectorAll("form#instituteAccessList li button");
            var instituteList = document.querySelectorAll("form#secureInternetList li button");
            var visibleServerCount = 0;
            var visibleInstituteCount = 0;

            serverList.forEach(function(entry) {
                var searchIn = entry.innerHTML + " " + entry.dataset.keywords + " " + entry.value;
                if(searchIn.toUpperCase().indexOf(search) !== -1) {
                    entry.parentElement.style.display = "block";
                    visibleServerCount++;
                } else {
                    entry.parentElement.style.display = "none";
                }
            });

            instituteList.forEach(function(entry) {
                var searchIn = entry.innerHTML + " " + entry.dataset.keywords + " " + entry.value;
                if(searchIn.toUpperCase().indexOf(search) !== -1) {
                    entry.parentElement.style.display = "block";
                    visibleInstituteCount++;
                } else {
                    entry.parentElement.style.display = "none";
                }
            });

            if(0 === visibleServerCount) {
                document.getElementById("instituteAccess").style.display = "none";
            } else {
                document.getElementById("instituteAccess").style.display = "block";
            }

            if(null !== document.getElementById("secureInternet")) {
                if(0 === visibleInstituteCount) {
                    document.getElementById("secureInternet").style.display = "none";
                } else {
                    document.getElementById("secureInternet").style.display = "block";
                }
            }

            var showOtherServer = false;
            // show "Add Manual" when search contains two dots and some text
            // between
            if(3 <= this.value.split(".").length) {
                // show "Other Server"
                document.querySelector("form#otherServerList button").value = this.value;
                document.querySelector("form#otherServerList button").innerHTML = this.value;
                document.getElementById("otherServer").style.display = "block";
                showOtherServer = true;
            } else {
                // hide "Other Server"
                document.getElementById("otherServer").style.display = "none";
            }

            if(0 === visibleServerCount && 0 === visibleInstituteCount && !showOtherServer) {
                document.getElementById("noResults").style.display = "block";
            } else {
                document.getElementById("noResults").style.display = "none";
            }

        });
    }
});
