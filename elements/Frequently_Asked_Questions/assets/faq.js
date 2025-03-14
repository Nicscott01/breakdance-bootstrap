(function () {
  const { mergeObjects } = BreakdanceFrontend.utils;

  class BreakdanceFaq {
    options = {
      accordion: false,
      openFirst: false,
    };

    constructor(selector, options) {
      this.selector = selector;
      this.options = mergeObjects(this.options, options || {});
      this.init();
    }

    toggleItem(event, item) {
      const targetEl = item ? item : event.target;
      const faqEl = targetEl.closest(".bde-faq__item");
      const isActive = faqEl.classList.contains("is-active");

      if (this.options.accordion) {
        this.closeAllItemsExcept(targetEl);
      }

      if (isActive) {
        this.closeItem(targetEl);
      } else {
        this.showItem(targetEl);
      }
    }

    closeAllItems() {
      this.elements.forEach((item) => this.closeItem(item));
    }

    closeAllItemsExcept(item) {
      this.elements
        .filter((el) => el !== item)
        .forEach((el) => this.closeItem(el));
    }

    isTransitioning(item) {
      return item.classList.contains("is-collapsing");
    }

    isActive(item) {
      return item.classList.contains("is-active");
    }

    async closeItem(item) {
      const faqEl = item.closest(".bde-faq__item");

      if (this.isTransitioning(faqEl)) return;
      if (!this.isActive(faqEl)) return;

      const faqButton = faqEl.querySelector(".bde-faq__question");
      const faqContent = faqEl.querySelector(".bde-faq__answer");

      const endHeight = this.getEndHeight(faqContent);
      faqContent.style.height = `${endHeight}px`;

      this.reflow(faqContent);

      faqEl.classList.add("is-collapsing");
      faqEl.classList.remove("is-active");
      faqContent.style.height = "";
      faqButton.setAttribute("aria-expanded", false);

      await this.onTransitionEnd(faqContent);

      faqEl.classList.remove("is-collapsing");
    }

    async showItem(item, instant = false) {
      const faqEl = item.closest(".bde-faq__item");

      if (this.isTransitioning(faqEl)) return;
      if (this.isActive(faqEl)) return;

      const faqButton = faqEl.querySelector(".bde-faq__question");
      const faqContent = faqEl.querySelector(".bde-faq__answer");

      const complete = () => {
        faqEl.classList.add("is-active");
        faqEl.classList.remove("is-collapsing");
        faqContent.style.height = "";
      };

      if (instant) {
        complete();
        return;
      }

      faqEl.classList.add("is-collapsing");
      faqContent.style.height = "0px";

      const endHeight = this.getEndHeight(faqContent);
      faqContent.style.height = `${endHeight}px`;
      faqButton.setAttribute("aria-expanded", true);

      await this.onTransitionEnd(faqContent);

      complete();
    }

    getEndHeight(element) {
      const height = element.getBoundingClientRect().height;
      return Math.max(element.scrollHeight, height);
    }

    reflow(element) {
      element.offsetHeight;
    }

    update(options = {}) {
      this.options = mergeObjects(this.options, options);
      this.destroy();
      this.init();
    }

    openFirst() {
      if (!this.elements[0]) return;
      this.showItem(this.elements[0], true);
    }

    onTransitionEnd(item) {
      const duration = window.getComputedStyle(item)
        .getPropertyValue("transition-duration");

      // If duration is 0s, transitionend will not fire.
      if (duration === "0s") {
        return Promise.resolve();
      }

      return new Promise((resolve) => {
        item.addEventListener("transitionend", resolve, { once: true });
      });
    }

    destroy() {
      this.elements = Array.from(document.querySelectorAll(
        `${this.selector} .js-faq-item`
      ));

      if (!this.elements) return;

      this.closeAllItems();
      this.elements.forEach((item) =>
        item.removeEventListener("click", this.onClick)
      );
    }

    bindClickListeners() {
      this.onClick = this.toggleItem.bind(this);
      this.elements.forEach((item) => {
        item.addEventListener("click", this.onClick);
      });
    }

    init() {
      this.elements = Array.from(document.querySelectorAll(
        `${this.selector} .js-faq-item`
      ));

      if (this.options.openFirst === true) {
        this.openFirst();
      }

      this.bindClickListeners();
    }
  }

  window.BreakdanceFaq = BreakdanceFaq;
})();
