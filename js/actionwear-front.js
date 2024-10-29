(function () {
	class ActionWearQuantityBox {
		constructor(quantities, type) {
			this.quantities = quantities;
			this.type = type;
			this.$ = jQuery;
			this.total_arrivals = parseInt(this.quantities.total_arrivals);
			this.quantity_supplier = parseInt(this.quantities.quantity_supplier);
			this.supplier_days = parseInt(this.quantities.supplier_days) ?? 0;
			this.brand = _ACTIONWEAR.brand;
		}
		getSimpleHtml() {
			const qty = this.total_arrivals;
			if (qty === 0) return "";
			const additional_label =
				qty > 0
					? qty +
					  " in riassortimento in " +
					  this.quantities?.arrivals_detail[0]?.numero_giorni +
					  " giorni circa"
					: "";
			return `
        <div class="my-1 text-green-700">${additional_label}</div>
        `;
		}
		getArrivalDetails() {
			let data_arrivi = "";
			if (this.quantities.arrivals_detail === null) return "";
			const count = this.quantities.arrivals_detail.length;
			if (count === 0) return "";
			return `
      <div class="md:flex flex-row gap-4 justify-between">
        ${this.quantities.arrivals_detail
					.map((detail) => {
						const qta =
							detail.qta !== ""
								? `
				<div>						
          <div class="flex">
              <div class="font-bold">Q.tà</div>
              <div class="ml-auto">${detail.qta}</div>
          </div>
		 		 ${(data_arrivi =
						detail.data_arrivi !== ""
							? `<div class="ml-auto text-xs">(${detail.data_arrivi})</div>`
							: "")}</div>`
								: "";
						return `
            <div class="border border-solid border-gray-200 p-2 md:p-4 mb-2 flex flex-col gap-2 rounded-md w-screen">
              ${qta}
            </div>`;
					})
					.join("")}
				</div>`;
		}
		getSupplierDetails() {
			let data_arrivi = "";
			if (this.quantities.supplier_detail === null) return "";
			const count = this.quantities.supplier_detail.length;
			if (count === 0) return "";
			return `
      <div class="md:flex flex-row gap-4 justify-between">
        ${this.quantities.supplier_detail
					.map((detail) => {
						const qta =
							detail.qta !== ""
								? `
					<div>						
      		  <div class="flex">
      		      <div class="font-bold">Q.tà</div>
      		      <div class="ml-auto">${detail.qta}</div>
      		  </div>
		  			${(data_arrivi =
							detail.data_arrivi !== ""
								? ` <div class="ml-auto text-xs">(${detail.data_arrivi})</div>`
								: "")}
						</div> `
								: "";
						return `
      					<div class="border border-solid border-gray-200 p-2 md:p-4 mb-2 flex flex-col gap-2 rounded-md w-screen">
      					  ${qta}
      				</div> `;
					})
					.join("")}
    		  </div> `;
		}
		getDetailedHtml() {
			const arrivals_detail =
				this.quantities.arrivals_detail !== null
					? `
          <div>
              <div class="toggle flex mb-2 cursor-pointer">
                  <div>Dettaglio riassortimenti previsti</div>
                  <div class="transition-all duration-300 ml-auto"> 
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-6">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                      </svg>
                  </div>
              </div>
              <div style="max-height:0; overflow:hidden; transition: all .3s; visibility:hidden;">${this.getArrivalDetails()}</div>
          </div>
      `
					: "";
			const supplier_detail =
				this.quantities.supplier_detail !== null &&
				this.type === "detailed_with_products"
					? `
          <div>
            <div class="toggle flex mb-2 cursor-pointer">
                <div>Dettaglio Magazzino ${this.brand}</div>
                <div class="transition-all duration-300 ml-auto"> 
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </div>
            <div style="max-height:0; overflow:hidden; transition: all .3s; visibility:hidden;">${this.getSupplierDetails()}</div>
        </div>
          `
					: "";
			const total_arrivals_label =
				this.quantities.arrivals_detail !== null
					? "(" +
					  this.quantities.arrivals_detail[0]?.numero_giorni +
					  " giorni circa)"
					: "";
			const quantity_supplier_label =
				this.quantities.supplier_detail !== null
					? "(" +
					  this.quantities.supplier_detail[0]?.numero_giorni +
					  " giorni circa)"
					: "";
			const first_supplier_available =
				this.quantities?.supplier_detail[0]?.qta ?? "";
			const additional_label =
				this.quantities.total_arrivals > 0
					? this.quantities.total_arrivals +
					  " in riassortimento in " +
					  this.quantities?.arrivals_detail[0]?.numero_giorni +
					  " giorni circa - guarda i dettagli"
					: "";
			const supplier_days_label =
				this.supplier_days > 0 ? `(${this.supplier_days} giorni circa)` : "";
			return `
        <div class="mb-1">Magazzino <span class="font-bold">${this.brand}</span>: ${this.quantities.quantity_supplier} disponibili in ${this.quantities.supplier_days} giorni circa</div>
        <div class="mb-1">${additional_label}</div>
        <div class="border border-solid border-gray-200 my-4 py-2 text-sm">
            <div class="flex">
                <div class="w-1/2 flex flex-col p-4">
					<div class='flex'>
                	    <div class="font-bold mb-2">In arrivo <span class="text-xs font-normal block">${total_arrivals_label}</span></div>
                	    <div class="ml-auto">${this.total_arrivals}</div>
					</div>
					${arrivals_detail}
				</div>
                <div class="w-1/2 flex flex-col p-4 pr-0">
					<div class='flex'>
                	    <div class="mb-2">Magazzino <span class="font-bold">${this.brand}</span><span class="text-xs font-normal block">${supplier_days_label}</span></div>
                	    <div class="ml-auto">${this.quantities.quantity_supplier}</div>
					</div>
					${supplier_detail}
				</div>
            </div>
        </div>
        `;
		}
		getHtml() {
			const $ = this.$;
			if (this.type === "simple") return this.getSimpleHtml();
			return this.getDetailedHtml();
		}
		render() {
			const $ = this.$;
			$("#actionwear_quantities").html(this.getHtml());
			$(".toggle").on("click", function () {
				const isVisible = $(this).next().css("max-height") !== "0px";
				$(this)
					.find("svg")
					.parent()
					.css("transform", isVisible ? "rotate(0)" : "rotate(-180deg)");
				$(this)
					.next()
					.css("max-height", isVisible ? "0px" : "300px");
				$(this)
					.next()
					.css("visibility", isVisible ? "hidden" : "visible");
				$(this)
					.next()
					.css("overflow", isVisible ? "hidden" : "auto");
			});
		}
	}

	jQuery(document).ready(function ($) {
		$("form.variations_form").on("found_variation", function () {
			const idChecker = setInterval(() => {
				const id = $(this).find('input[name="variation_id"]').val();
				if (id !== "") clearInterval(idChecker);
				if (Object.keys(_ACTIONWEAR.product_quantities_info).includes(id)) {
					const quantities = _ACTIONWEAR.product_quantities_info[id];
					const box = new ActionWearQuantityBox(
						quantities,
						_ACTIONWEAR.product_quantities_view_type
					);
					box.render();
				}
			}, 600);
		});
	});
})();
