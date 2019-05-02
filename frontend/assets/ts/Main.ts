import * as $ from "jquery";
import Accesses from "./settings/Accesses";
import Apps from "./settings/Apps";

$(() => {
	Apps.initIfNeeded();
	Accesses.initIfNeeded();
});