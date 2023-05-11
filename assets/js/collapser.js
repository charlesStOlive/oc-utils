function updateCollapseStates() {
  var collapseElements = document.querySelectorAll("[data-attribute='w-collapse']");

  collapseElements.forEach(function (element) {
    var title = element.querySelector(".w-collapse__title");
    var icon = element.querySelector(".w-collapse__icon");
    var content = element.querySelector(".w-collapse__content");
    var defaultState = window.innerWidth >= 992 ? element.getAttribute("data-default-lg") || "open" : "closed";

    if (defaultState === "open") {
      content.style.display = "block";
      icon.classList.remove("icon-plus-square-o");
      icon.classList.add("icon-minus-square-o");
    } else {
      content.style.display = "none";
      icon.classList.remove("icon-minus-square-o");
      icon.classList.add("icon-plus-square-o");
    }
  });
}

document.addEventListener("DOMContentLoaded", function () {
  updateCollapseStates();
  window.addEventListener("resize", updateCollapseStates);

  var collapseElements = document.querySelectorAll("[data-attribute='w-collapse']");

  collapseElements.forEach(function (element) {
    var title = element.querySelector(".w-collapse__title");
    var icon = element.querySelector(".w-collapse__icon");
    var content = element.querySelector(".w-collapse__content");

    title.addEventListener("click", function () {
      if (content.style.display === "none") {
        content.style.display = "block";
        icon.classList.remove("icon-plus-square-o");
        icon.classList.add("icon-minus-square-o");
      } else {
        content.style.display = "none";
        icon.classList.remove("icon-minus-square-o");
        icon.classList.add("icon-plus-square-o");
      }
    });
  });
});
