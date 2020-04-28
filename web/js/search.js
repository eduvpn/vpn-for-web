"use strict";

document.addEventListener("DOMContentLoaded", function () {
    if(null !== document.querySelector("form.search")) {
        document.querySelector("form.search").addEventListener("submit", function (e) {
            // disable standard form submit when JS is enabled for the search box
            e.preventDefault();
        });

        document.querySelector("form.search input").addEventListener("keyup", function () {
            var search = this.value.toUpperCase();
            var instituteList = document.querySelectorAll("form.instituteList li button");
            var visibleCount = 0;
            instituteList.forEach(function(entry) {
                var instituteName = entry.innerHTML;
                if(instituteName.toUpperCase().indexOf(search) !== -1) {
                    entry.parentElement.style.display = "block";
                    visibleCount++;
                } else {
                    entry.parentElement.style.display = "none";
                }
            });
            if(0 === visibleCount) {
                document.getElementById("instituteAccess").style.display = "none";
            } else {
                document.getElementById("instituteAccess").style.display = "block";
            }
        });

        document.querySelector("form.search input").addEventListener("keyup", function () {
            var search = this.value.toUpperCase();
            var idpList = document.querySelectorAll("form.idpList li button");
            var visibleCount = 0;
            idpList.forEach(function(entry) {
                var idpName = entry.innerHTML;
                if(idpName.toUpperCase().indexOf(search) !== -1) {
                    entry.parentElement.style.display = "block";
                    visibleCount++;
                } else {
                    entry.parentElement.style.display = "none";
                }
            });
            if(0 === visibleCount) {
                document.getElementById("secureInternet").style.display = "none";
            } else {
                document.getElementById("secureInternet").style.display = "block";
            }
        });

    }
});
