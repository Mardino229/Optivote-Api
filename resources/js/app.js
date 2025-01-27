import './bootstrap';
import 'flowbite';
import * as simpleDatatables from "simple-datatables";


if (document.getElementById("search-table") && typeof simpleDatatables.DataTable !== 'undefined') {
    const dataTable = new simpleDatatables.DataTable("#search-table", {
        searchable: true,
        sortable: false
    });
}
