;(function ($) {
    "use strict";
    /*
     * guiStyles that free-jqgrid doesn't ship: bootstrap5 and phpGrid's own "modern".
     *
     * With an unregistered guiStyle every state class resolves to "", and
     * setSelection() treats a row as disabled when
     * row.className.indexOf(disabledClass) > -1 -- indexOf("") is 0, so no row can
     * ever be selected (Edit/View/Delete then report "Please, select row").
     *
     * Both styles only name the row/cell states; every other path keeps resolving
     * to "", so the grid markup carries no framework classes.
     */
    if (!$.jgrid || !$.jgrid.guiStyles) {
        return;
    }

    var guiStyles = $.jgrid.guiStyles;

    if (!guiStyles.bootstrap5) {
        guiStyles.bootstrap5 = {
            states: {
                select: "table-active",
                disabled: "disabled ui-state-disabled",
                hover: "table-hover-row",
                highlight: "table-highlight",
                active: "active",
                error: "alert-danger",
                textOfClickable: ""
            }
        };
    }

    // css/modern/pg-jqgrid-modern.css styles these; no framework required
    if (!guiStyles.modern) {
        guiStyles.modern = {
            states: {
                select: "pg-selected",
                disabled: "pg-disabled ui-state-disabled",
                hover: "pg-hover",
                highlight: "pg-highlight",
                active: "pg-active",
                error: "pg-error",
                textOfClickable: ""
            }
        };
    }
}(jQuery));
