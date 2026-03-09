/*jslint browser: true*/
//Revision: 2018.02.13

const whippet = {
  state: {
    restoreAfterSubmitKey: "whippetRestoreAfterSubmit",
    activeViewKey: "whippetActiveView",
    getStorage() {
      "use strict";
      try {
        return window.sessionStorage;
      } catch (error) {
        return null;
      }
    },
    setValue(key, value) {
      "use strict";
      const storage = whippet.state.getStorage();
      if (!storage) {
        return;
      }
      storage.setItem(key, value);
    },
    getValue(key) {
      "use strict";
      const storage = whippet.state.getStorage();
      if (!storage) {
        return "";
      }
      return storage.getItem(key) || "";
    },
    setRestoreAfterSubmit() {
      "use strict";
      whippet.state.setValue(whippet.state.restoreAfterSubmitKey, "1");
    },
    getRestoreAfterSubmit() {
      "use strict";
      return whippet.state.getValue(whippet.state.restoreAfterSubmitKey) === "1";
    },
    clearRestoreAfterSubmit() {
      "use strict";
      const storage = whippet.state.getStorage();
      if (!storage) {
        return;
      }
      storage.removeItem(whippet.state.restoreAfterSubmitKey);
    },
    setActiveView(viewName) {
      "use strict";
      if (!viewName) {
        return;
      }
      whippet.state.setValue(whippet.state.activeViewKey, viewName);
    },
    getActiveView() {
      "use strict";
      return whippet.state.getValue(whippet.state.activeViewKey);
    },
    applyActiveView(panel, targetView) {
      "use strict";
      let buttons;
      let views;
      if (!panel || !targetView) {
        return;
      }

      buttons = panel.querySelectorAll("[data-whippet-view-target]");
      Array.prototype.forEach.call(buttons, function (button) {
        button.classList.toggle(
          "is-active",
          button.getAttribute("data-whippet-view-target") === targetView
        );
        button.setAttribute(
          "aria-pressed",
          button.getAttribute("data-whippet-view-target") === targetView
            ? "true"
            : "false"
        );
      });

      views = panel.querySelectorAll("[data-whippet-view]");
      Array.prototype.forEach.call(views, function (view) {
        view.classList.toggle(
          "is-active",
          view.getAttribute("data-whippet-view") === targetView
        );
      });
    },
    getCurrentActiveView(panel) {
      "use strict";
      const activeView = panel
        ? panel.querySelector("[data-whippet-view].is-active")
        : null;
      return activeView ? activeView.getAttribute("data-whippet-view") || "" : "";
    },
    restore(panel) {
      "use strict";
      if (!panel) {
        return;
      }

      if (!whippet.state.getRestoreAfterSubmit()) {
        return;
      }

      panel.style.display = "block";
      whippet.state.applyActiveView(
        panel,
        whippet.state.getActiveView() || "script-manager"
      );
      whippet.state.clearRestoreAfterSubmit();
    },
  },
  menu: {
    init() {
      "use strict";
      document.addEventListener("click", function (e) {
        const menuItem = e.target && e.target.closest ? e.target.closest("#wp-admin-bar-whippet") : null;
        if (!menuItem) {
          return;
        }
        e.preventDefault();
        const panel = document.getElementById("whippet");
        if (panel) {
          if (panel.style.display === "none") {
            panel.style.display = "block";
          } else {
            panel.style.display = "none";
          }
        }
      });
    },
  },
  UI: {
    init() {
      "use strict";
      let elements;
      const panel = document.getElementById("whippet");

      whippet.state.restore(panel);

      elements = document.querySelectorAll("#whippet input[type=checkbox]");
      Array.prototype.forEach.call(elements, function (el) {
        el.addEventListener("change", function () {
          document.whippetChanged = true;
        });
      });

      elements = document.querySelectorAll("#whippet select");
      Array.prototype.forEach.call(elements, function (el) {
        el.addEventListener("change", function () {
          const selectCell = this.closest ? this.closest("td") : null;
          if (
            !selectCell ||
            !selectCell.classList ||
            !selectCell.classList.contains("option-everwhere")
          ) {
            document.whippetChanged = true;
            return;
          }

          //look for wrappers
          let tr;
          if (this.parentNode.tagName.toLowerCase() === "td") {
            tr = this.parentNode.parentNode;
          } else {
            tr = this.parentNode.parentNode.parentNode; //probably wrapper around select
          }
          const sectionCond = tr.querySelector(".g-cond");
          const sectionExcp = tr.querySelector(".g-excp");
          const sectionRegex = tr.querySelector(".g-regex");
          const checkedRadio = tr.querySelector(
            ".g-cond input[type=radio]:checked"
          );

          if (this.value === "e") {
            /**
             * State = Enable
             */

            //ukrywanie sekcji "Where" i "Exceptions"
            whippet.helper.addClass("g-disabled", sectionCond);
            whippet.helper.addClass("g-disabled", sectionExcp);
            if (sectionRegex) {
              whippet.helper.addClass("g-disabled", sectionRegex);
            }

            if (sectionCond) {
              whippet.helper.clearSelected(sectionCond);
            }
            if (checkedRadio) {
              //odznaczanie stanu "Where"
              checkedRadio.checked = false;

              //odznaczanie "Exceptions"
              const tr2 = this.parentNode.parentNode.parentNode;
              const section = tr2.querySelector(".g-excp");
              whippet.helper.clearSelected(section);
            }
          } else {
            /**
             * State = Disable
             */

            //pokazywanie sekcji "where"
            whippet.helper.removeClass("g-disabled", sectionCond);

            // Asset rows now use a single disable panel; plugin rows still use radios.
            if (!checkedRadio) {
              if (sectionExcp) {
                whippet.helper.addClass("g-disabled", sectionExcp);
              }
              if (sectionRegex) {
                whippet.helper.addClass("g-disabled", sectionRegex);
              }
            } else if (checkedRadio.value === "everywhere") {
              //jeśli zaznaczono "Everywhere" pokaż tą sekcję
              if (sectionRegex) {
                whippet.helper.addClass("g-disabled", sectionRegex);
              }
              whippet.helper.removeClass("g-disabled", sectionExcp);
            } else if (checkedRadio && checkedRadio.value === "regex") {
              whippet.helper.addClass("g-disabled", sectionExcp);

              if (sectionRegex) {
                whippet.helper.removeClass("g-disabled", sectionRegex);
              }
            }
          }

          document.whippetChanged = true;
        });
      });

      elements = document.querySelectorAll(
        "#whippet .g-cond input[type=radio]"
      );
      Array.prototype.forEach.call(elements, function (el) {
        el.addEventListener("change", function () {
          const tr = this.parentNode.parentNode.parentNode;
          const sectionExcp = tr.querySelector(".g-excp");
          const sectionRegex = tr.querySelector(".g-regex");

          if (this.value === "here") {
            whippet.helper.addClass("g-disabled", sectionExcp);
            if (sectionRegex) {
              whippet.helper.addClass("g-disabled", sectionRegex);
            }
            whippet.helper.clearSelected(sectionExcp);
            if (sectionRegex) {
              whippet.helper.clearSelected(sectionRegex);
            }
          } else if (this.value === "everywhere") {
            if (sectionRegex) {
              whippet.helper.addClass("g-disabled", sectionRegex);
            }
            whippet.helper.removeClass("g-disabled", sectionExcp);
          } else if (this.value === "regex") {
            whippet.helper.addClass("g-disabled", sectionExcp);
            if (sectionRegex) {
              whippet.helper.removeClass("g-disabled", sectionRegex);
            }
          }

          document.whippetChanged = true;
        });
      });

      const submitButton = document.getElementById("submit-whippet");
      if (submitButton) {
        submitButton.addEventListener("click", function () {
          const currentPanel = document.getElementById("whippet");
          document.whippetChanged = false;
          whippet.state.setRestoreAfterSubmit();
          whippet.state.setActiveView(
            whippet.state.getCurrentActiveView(currentPanel) || "script-manager"
          );
        });
      }

      document.addEventListener("click", function (e) {
        const viewButton =
          e.target && e.target.closest
            ? e.target.closest("#whippet [data-whippet-view-target]")
            : null;
        const cleanupButton =
          e.target && e.target.closest
            ? e.target.closest("#whippet .whippet-global-view__cleanup")
            : null;
        const deleteButton =
          e.target && e.target.closest
            ? e.target.closest("#whippet .whippet-global-view__delete")
            : null;
        let panel;
        let targetView;
        let buttons;
        let views;
        let deletionsContainer;

        if (viewButton) {
          panel = document.getElementById("whippet");
          targetView = viewButton.getAttribute("data-whippet-view-target");
          if (!panel || !targetView) {
            return;
          }

          whippet.state.applyActiveView(panel, targetView);
          whippet.state.setActiveView(targetView);

          return;
        }

        if (cleanupButton) {
          deletionsContainer = document.getElementById(
            "whippet-global-view-deletions"
          );
          if (!deletionsContainer) {
            return;
          }

          whippet.helper.appendDeleteRules(
            deletionsContainer,
            cleanupButton.getAttribute("data-whippet-delete-rules") || ""
          );
          whippet.helper.removeRowsByKeys(
            cleanupButton.getAttribute("data-whippet-delete-row-keys") || ""
          );
          cleanupButton.disabled = true;
          cleanupButton.textContent = "Outdated post IDs queued";
          document.whippetChanged = true;
          return;
        }

        if (!deleteButton) {
          return;
        }

        deletionsContainer = document.getElementById(
          "whippet-global-view-deletions"
        );
        if (!deletionsContainer) {
          return;
        }

        if (
          !window.confirm(
            'Are you sure you want to delete the rule for "' +
              (deleteButton.getAttribute("data-whippet-delete-label") || "this item") +
              '"?'
          )
        ) {
          return;
        }

        whippet.helper.appendDeleteRule(
          deletionsContainer,
          deleteButton.getAttribute("data-whippet-delete-rule") || ""
        );
        whippet.helper.removeRuleRow(deleteButton.closest("tr"));

        document.whippetChanged = true;
      });

      const resetButton = document.getElementById("whippet-frontend-reset-btn");
      const resetMessage = document.getElementById("whippet-frontend-reset-msg");
      if (resetButton) {
        resetButton.addEventListener("click", function () {
          if (
            !window.confirm(
              "Are you sure? This will remove all Script Manager disabled/enabled rules. This cannot be undone."
            )
          ) {
            return;
          }

          resetButton.disabled = true;
          if (resetMessage) {
            resetMessage.textContent = "Resetting...";
            resetMessage.style.color = "#64748b";
          }

          fetch(resetButton.getAttribute("data-whippet-ajax-url"), {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
              action: "whippet_scripts_reset",
              nonce:
                resetButton.getAttribute("data-whippet-reset-nonce") || "",
            }),
          })
            .then(function (response) {
              return response.json();
            })
            .then(function (result) {
              resetButton.disabled = false;
              if (!resetMessage) {
                return;
              }

              if (result.success) {
                resetMessage.style.color = "#00a32a";
                resetMessage.textContent = result.data.message;
              } else {
                resetMessage.style.color = "#d63638";
                resetMessage.textContent = result.data || "Reset failed.";
              }
            })
            .catch(function () {
              resetButton.disabled = false;
              if (resetMessage) {
                resetMessage.style.color = "#d63638";
                resetMessage.textContent = "Request failed.";
              }
            });
        });
      }
    },
    protection() {
      "use strict";
      window.addEventListener("beforeunload", function (e) {
        if (document.whippetChanged) {
          const confirmationMessage =
            "It looks like you have been editing configuration and tried to leave page without saving. Press cancel to stay on page.";
          (e || window.event).returnValue = confirmationMessage;
          return confirmationMessage;
        }
      });
    },
  },
  helper: {
    addClass(className, el) {
      "use strict";
      if (!el) {
        return;
      }
      if (el.classList) {
        el.classList.add(className);
      } else {
        el.className += " " + className;
      }
    },
    removeClass(className, el) {
      "use strict";
      if (!el) {
        return;
      }
      if (el.classList) {
        el.classList.remove(className);
      } else {
        el.className = el.className.replace(
          new RegExp(
            "(^|\\b)" + className.split(" ").join("|") + "(\\b|$)",
            "gi"
          ),
          " "
        );
      }
    },
    clearSelected(el) {
      "use strict";
      if (!el) {
        return;
      }
      const checkboxes = el.querySelectorAll("input[type=checkbox]");
      Array.prototype.forEach.call(checkboxes, function (input) {
        input.checked = false;
      });

      const textareas = el.querySelectorAll("textarea");
      Array.prototype.forEach.call(textareas, function (input) {
        input.value = "";
      });

      const textInputs = el.querySelectorAll('input[type="text"]');
      Array.prototype.forEach.call(textInputs, function (input) {
        input.value = "";
      });

      const selects = el.querySelectorAll("select");
      Array.prototype.forEach.call(selects, function (input) {
        input.selectedIndex = 0;
      });
    },
    appendDeleteRule(container, value) {
      "use strict";
      let input;
      if (!container || !value) {
        return;
      }

      input = document.createElement("input");
      input.type = "hidden";
      input.name = "whippet_delete_rules[]";
      input.value = value;
      container.appendChild(input);
    },
    appendDeleteRules(container, encodedRules) {
      "use strict";
      let rules;
      try {
        rules = JSON.parse(window.atob(encodedRules || ""));
      } catch (error) {
        return;
      }

      if (!Array.isArray(rules)) {
        return;
      }

      Array.prototype.forEach.call(rules, function (rule) {
        whippet.helper.appendDeleteRule(container, rule);
      });
    },
    ensureEmptyState(tbody) {
      "use strict";
      let emptyRow;
      if (!tbody || tbody.querySelector("tr")) {
        return;
      }

      emptyRow = document.createElement("tr");
      emptyRow.className = "whippet-global-view__empty-row";
      emptyRow.innerHTML = '<td colspan="4">No rules found in this section.</td>';
      tbody.appendChild(emptyRow);
    },
    removeRuleRow(row) {
      "use strict";
      let tbody;
      let footer;
      if (!row || !row.parentNode) {
        return;
      }

      tbody = row.parentNode;
      footer = tbody.parentNode.nextElementSibling;
      row.parentNode.removeChild(row);
      whippet.helper.ensureEmptyState(tbody);

      if (
        footer &&
        footer.classList &&
        footer.classList.contains("whippet-global-view__footer") &&
        !tbody.querySelector("tr.is-outdated")
      ) {
        footer.parentNode.removeChild(footer);
      }
    },
    removeRowsByKeys(encodedKeys) {
      "use strict";
      let keys;
      try {
        keys = JSON.parse(window.atob(encodedKeys || ""));
      } catch (error) {
        return;
      }

      if (!Array.isArray(keys)) {
        return;
      }

      Array.prototype.forEach.call(keys, function (key) {
        const row = document.querySelector(
          '#whippet [data-whippet-row-key="' + key + '"]'
        );
        whippet.helper.removeRuleRow(row);
      });
    },
  },
  ready(fn) {
    "use strict";
    if (document.readyState !== "loading") {
      fn();
    } else {
      document.addEventListener("DOMContentLoaded", fn);
    }
  },
  init() {
    "use strict";
    whippet.ready(whippet.menu.init);
    whippet.ready(whippet.UI.init);
    whippet.ready(whippet.UI.protection);
  },
};

setTimeout(function () {
  whippet.init();
}, 100);
