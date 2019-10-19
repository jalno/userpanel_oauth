import "bootstrap";
import "bootstrap-inputmsg";
import * as moment from "jalali-moment";
import * as $ from "jquery";
import "jquery.growl";
import "webuilder";
import { Router } from "webuilder";
import "webuilder/formAjax";
import { IApp } from "./Apps";

enum AccessStatus {
	ACTIVE = 1,
	DEACTIVE = 2,
}
interface IAccess {
	id: number;
	user_id: number;
	user: {
		id: number;
		name: string;
		lastname?: string;
	};
	app: IApp;
	lastuse_at?: number;
	lastip?: string;
	token?: string;
	code?: string;
	status: AccessStatus;
}

export default class Accesses {
	public static initIfNeeded() {
		if ($("body").hasClass("userpanel_oauth-accesses")) {
			Accesses.init();
		}
	}
	protected static init() {
		Accesses.runUserAutoComplete();
		Accesses.runDeleteListener();
		Accesses.runAddSubmitListener();
		Accesses.listenAppRowEvents($(".table-accesses"));
		Accesses.runClipboard();
	}
	protected static runUserAutoComplete() {
		$("#accesses-search-form input[name=user_name]").userAutoComplete();
		$("#accesses-add input[name=user_name]").userAutoComplete();
	}
	protected static runAddSubmitListener() {
		const $form = $("#accesses-add");
		const $btn = $(".panel-accesses .btn-submit");
		$form.on("submit", function(e) {
			e.preventDefault();
			$btn.prop("disabled", true);
			$(this).formAjax({
				success: (data) => {
					$btn.prop("disabled", false);
					Accesses.rebuildAccessRow(data.access);
					Accesses.showAccessSecret(data.access);
				},
				error: (error) => {
					$btn.prop("disabled", false);
					Accesses.ajaxErrorHandler.call(this, error);
				},
			});
		});
	}
	protected static runDeleteListener() {
		const $modal = $("#access-delete");
		const $btn = $(".btn-submit", $modal);
		$("#access-delete-form").on("submit", function(e) {
			e.preventDefault();
			$btn.prop("disabled", true);
			const access = $modal.data("access") as IAccess;
			$(this).formAjax({
				url: `userpanel/settings/accesses/${access.id}/delete?ajax=1`,
				type: "POST",
				success: () => {
					$("#access-" + access.id).remove();
					$modal.modal("hide");
				},
				error: (error) => {
					$btn.prop("disabled", false);
					Accesses.ajaxErrorHandler.call(this, error);
				},
			});
		});
	}

	private static rebuildAccessRow(access: IAccess) {
		const $table = $(".table-accesses");
		const multiuser = $(".user-th", $table).length > 0;
		let status = "";
		switch (access.status) {
			case (AccessStatus.ACTIVE):
				status = `<span class="label label-success">${t("userpanel_oauth.access.status.active")}</span>`;
				break;
			case (AccessStatus.DEACTIVE):
				status = `<span class="label label-danger">${t("userpanel_oauth.access.status.deactive")}</span>`;
				break;
		}
		const canDelete = $table.data("can-delete") as boolean;
		let buttons = "";
		if (canDelete) {
			buttons += ` <a href="#" class="btn btn-xs btn-bricky btn-delete tooltips" title="حذف"><i class="fa fa-times"></i></a>`;
		}
		let inner  = `<td class="center">${access.id}</td>`;
		inner += `<td>${access.app.logo ? `<img src="${access.app.logo}" class="app-logo">` : `<i class="fa fa-rocket app-logo"></i>`} ${access.app.name}</td>`;
		if (multiuser) {
			inner += `<td><a href="${Router.url("userpanel/users", {id: access.user_id})}" target="_blank">${access.user.name + " " + access.user.lastname}</a></td>`;
		}
		inner += `<td class="ltr center">${access.lastuse_at ? moment(access.lastuse_at * 1000).locale("fa").format("YYYY/MM/DD HH:mm:ss") + "<br>" + access.lastip : "-"}</td>`;
		inner += `<td>${status}</td>`;
		if (buttons) {
			inner += `<td>${buttons}</td>`;
		}
		let $tr = $(`#access-${access.id}`, $table);
		if ($tr.length) {
			$tr.html(inner);
		} else {
			$tr = $(`<tr id="access-${access.id}">${inner}</tr>`).prependTo($table.find("tbody"));
		}
		$tr.data("access", access);
		$(".tooltips", $tr).tooltip();
		this.listenAppRowEvents($tr);
	}
	private static listenAppRowEvents($container: JQuery) {
		$(".btn-delete", $container).on("click", function(e) {
			e.preventDefault();
			const $tr = $(this).parents("tr");
			const access = $tr.data("access") as IAccess;
			const $modal = $("#access-delete");
			$(".access-id", $modal).html(access.id.toString());
			$modal.data("access", access).modal("show");
		});
	}
	private static ajaxErrorHandler(error) {
		if (error.error === "data_duplicate" || error.error === "data_validation") {
			const $input = $(`[name="${error.input}"]`, this);
			const $params = {
				title: t("error.fatal.title"),
				message: t(error.error),
			};
			if ($input.length) {
				$input.inputMsg($params);
			} else {
				$params.message = t("userpane.formajax.error");
				$.growl.error($params);
			}
		} else {
			$.growl.error({
				title: t("error.fatal.title"),
				message: t("userpane.formajax.error"),
			});
		}
	}

	private static showAccessSecret(access: IAccess) {
		const $modal = $("#access-show");
		$modal.modal("show");
		$(".btn-copy", $modal).tooltip("destroy");
		const $logo = $(".app-logo", $modal);
		const logo  = access.app.logo || $logo.data("default");
		$(".app-logo", $modal).attr("src", logo);
		$("input[name=code]", $modal).val(access.code);
		$("input[name=token]", $modal).val(access.token);
	}

	private static runClipboard() {
		$(".btn-copy").on("click", function(e) {
			e.preventDefault();
			const $input = $(this).parents(".form-group").find("input");
			Accesses.copy($input.val() as string);
			$(this).tooltip({
				title: t("userpanel_oauth.copied"),
			}).tooltip("show");
		});
	}
	private static copy(str: string) {
		const el = document.createElement("textarea");
		el.value = str;
		el.setAttribute("readonly", "");
		el.style.position = "absolute";
		el.style.right = "-9999px";
		document.body.appendChild(el);
		el.select();
		document.execCommand("copy");
		document.body.removeChild(el);
	}
}
