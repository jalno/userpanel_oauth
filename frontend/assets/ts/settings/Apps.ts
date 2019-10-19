import "@jalno/translator";
import "bootstrap";
import {AvatarPreview} from "bootstrap-avatar-preview/AvatarPreview";
import * as $ from "jquery";
import "jquery.growl";
import {Router} from "webuilder";
import "../jquery.userAutoComplete";

export enum AppStatus {
	ACTIVE = 1,
	DEACTIVE = 2,
}
export interface IApp {
	id: number;
	name: string;
	user_id: number;
	user: {
		name: string;
		lastname: string | undefined;
	};
	token: string;
	secret: string;
	logo?: string;
	ip?: string;
	status: AppStatus;
}

export default class Apps {
	public static initIfNeeded(): void {
		if ($("body").hasClass("userpanel_oauth-apps")) {
			this.init();
		}
	}
	public static init() {
		this.runUserAutoComplete();
		this.runDeleteAppsListener();
		if ($("#apps-add").length) {
			this.initLogoPreview();
			this.runAddAppsSubmitListener();
		}
		this.runClipboard();
		this.listenAppRowEvents($(".table-apps"));
	}
	private static initLogoPreview() {
		new AvatarPreview($(".app-logo-preview"));
	}
	private static runUserAutoComplete() {
		$("#apps-search-form input[name=search_user_name]").userAutoComplete();
		$("#apps-add input[name=user_name]").userAutoComplete();
	}
	private static runDeleteAppsListener() {
		const $modal = $("#app-delete");
		const $btn = $(".btn-submit", $modal);
		$("form", $modal).on("submit", function(e) {
			e.preventDefault();
			$btn.prop("disabled", true);
			const app = $modal.data("app") as IApp;
			$(this).formAjax({
				url: `userpanel/settings/apps/${app.id}/delete?ajax=1`,
				dataType: "json",
				type: "POST",
				success: () => {
					$("#app-" + app.id).remove();
					$modal.modal("hide");
					Apps.resetForm();
				},
				error: (error) => {
					$btn.prop("disabled", false);
					if (error.error === "data_duplicate" || error.error === "data_validation") {
						const $input = $(`[name="${error.input}"]`, this);
						const $params = {
							title: t("error.fatal.title"),
							message: t(error.error),
						};
						if ($input.length) {
							$input.inputMsg($params);
						} else {
							$params.message = t("userpanel.formajax.error");
							$.growl.error($params);
						}
					} else {
						$.growl.error({
							title: t("error.fatal.title"),
							message: t("userpanel.formajax.error"),
						});
					}
				},
			});
		});
	}
	private static runAddAppsSubmitListener() {
		const $form = $("#apps-add");
		const $btn = $(".btn-submit", $form);
		$form.on("submit", (e) => {
			e.preventDefault();
			$btn.prop("disabled", true);
			$form.formAjax({
				dataType: "json",
				data: new FormData($form[0] as HTMLFormElement),
				contentType: false,
				processData: false,
				success: (data) => {
					if (!$("#app-" + data.app.id).length) {
						this.showAppSecret(data.app);
					}
					this.rebuildAppRow(data.app);
					this.resetForm();
					$.growl.notice({
						title: t("userpane.success"),
						message: t("userpane.formajax.success"),
					});
				},
				error: (error) => {
					$btn.prop("disabled", false);
					if (error.error === "data_duplicate" || error.error === "data_validation") {
						const $input = $(`[name="${error.input}"]`, $form);
						const $params = {
							title: t("error.fatal.title"),
							message: t(error.error),
						};
						if ($input.length) {
							$input.inputMsg($params);
						} else {
							$params.message = t("userpanel.formajax.error");
							$.growl.error($params);
						}
					} else {
						$.growl.error({
							title: t("error.fatal.title"),
							message: t("userpanel.formajax.error"),
						});
					}
				},
			});
		});
		$(".panel-apps .btn-reset").on("click", (e) => {
			e.preventDefault();
			this.resetForm();
		});
	}
	private static showAppSecret(app: IApp) {
		const $modal = $("#app-show");
		$modal.modal("show");
		$(".btn-copy", $modal).tooltip("destroy");
		const $logo = $(".app-logo", $modal);
		const logo  = app.logo || $logo.data("default");
		$(".app-logo", $modal).attr("src", logo);
		$("input[name=token]", $modal).val(app.token);
		$("input[name=secret]", $modal).val(app.secret);
	}
	private static runClipboard() {
		$(".btn-copy").on("click", function(e) {
			e.preventDefault();
			const $input = $(this).parents(".form-group").find("input");
			Apps.copy($input.val() as string);
			$(this).tooltip({
				title: "کپی شد!",
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
	private static rebuildAppRow(app: IApp) {
		const $table = $(".table-apps");
		const multiuser = $(".user-th", $table).length > 0;
		let status = "";
		switch (app.status) {
			case (AppStatus.ACTIVE):
				status = `<span class="label label-success">فعال</span>`;
				break;
			case (AppStatus.DEACTIVE):
				status = `<span class="label label-danger">غیرفعال</span>`;
				break;
		}
		const canEdit = $table.data("can-edit") as boolean;
		const canDelete = $table.data("can-delete") as boolean;
		let buttons = "";
		if (canEdit) {
			buttons += ` <a href="#" class="btn btn-xs btn-teal btn-edit tooltips" title="${t("userpanel.edit")}"><i class="fa fa-edit"></i></a>`;
		}
		if (canDelete) {
			buttons += ` <a href="#" class="btn btn-xs btn-bricky btn-delete tooltips" title="${t("userpanel.delete")}"><i class="fa fa-times"></i></a>`;
		}
		let inner  = `<td class="center">${app.id}</td>`;
		inner += `<td>${app.logo ? `<img src="${app.logo}" class="app-logo">` : `<i class="fa fa-rocket app-logo"></i>`} ${app.name}</td>`;
		if (multiuser) {
			inner += `<td><a href="${Router.url("userpanel/users", {id: app.user_id})}" target="_blank">${app.user.name + " " + app.user.lastname}</a></td>`;
		}
		inner += `<td class="ltr center">${app.ip || `<i class="fa fa-times-circle-o"></i>`}</td>`;
		inner += `<td>${status}</td>`;
		if (buttons) {
			inner += `<td>${buttons}</td>`;
		}
		let $tr = $(`#app-${app.id}`, $table);
		if ($tr.length) {
			$tr.html(inner);
		} else {
			$tr = $(`<tr id="app-${app.id}">${inner}</tr>`).prependTo($table.find("tbody"));
		}
		$tr.data("app", app);
		$(".tooltips", $tr).tooltip();
		this.listenAppRowEvents($tr);
	}
	private static listenAppRowEvents($container: JQuery) {
		$(".btn-edit", $container).on("click", function(e) {
			e.preventDefault();
			const $tr = $(this).parents("tr");
			const app = $tr.data("app") as IApp;
			Apps.loadAppToForm(app);
		});
		$(".btn-delete", $container).on("click", function(e) {
			e.preventDefault();
			const $tr = $(this).parents("tr");
			const app = $tr.data("app");
			const $modal = $("#app-delete");
			$(".app-id", $modal).html(app.id);
			$modal.data("app", app).modal("show");
		});
	}
	private static loadAppToForm(app: IApp) {
		const $form = $("#apps-add");
		$form.data("app", app.id);
		$form.attr("action", Router.url(`userpanel/settings/apps/${app.id}/edit`));
		const logo  = app.logo || $(".app-logo-preview .btn-remove").data("default");
		$(".app-logo-preview img.preview", $form).attr("src", logo);
		$("input[name=name]", $form).val(app.name);
		$("input[name=ip]", $form).val(app.ip);
		$("input[name=user]", $form).val(app.user_id);
		$("input[name=user_name]", $form).val(app.user.name + (app.user.lastname ? " " + app.user.lastname : ""));
		$("select[name=status]", $form).val(app.status);
		$(".panel-apps .btn-reset").show();
	}
	private static resetForm() {
		const $form = $("#apps-add");
		$form.removeData("app");
		$form.attr("action", Router.url("userpanel/settings/apps/add"));
		$(".app-logo-preview img.preview", $form).attr("src", $(".app-logo-preview .btn-remove").data("default"));
		($form[0] as HTMLFormElement).reset();
		$(".panel-apps .btn-reset").hide();
	}
}
