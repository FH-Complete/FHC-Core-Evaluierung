import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";

export default {
	components: {
		FormForm,
		FormInput
	},
	props: {
		frage: Object,
		modelValue: Object
	},
	emits: ['update:modelValue'],
	computed: {
		selected: {
			get() {
				return this.modelValue;
			},
			set(value) {
				this.$emit('update:modelValue', value);
			}
		}
	},
	methods: {
		getIconLabel(antwortWert) {
			return '<i class="fa-regular fa-' + this.getIcon(antwortWert) + ' fa-2x"></i>';
		},
		getIcon(antwortWert) {
			const icons = [
				'circle-question',	// 0
				'face-laugh',		// 1
				'face-smile',		// 2
				'face-meh',			// 3
				'face-frown',		// 4
				'face-tired'		// 5
			];

			return Number.isInteger(antwortWert) && antwortWert >= 0 && antwortWert <= 5
				? icons[antwortWert]
				: 'circle-question';
		}
	},
	template: `  
  	<div class="fragebogen-frage">
  	
		<!-- Fragenbogenfrage SingleResponse -->	
		<div v-if="frage.typ === 'singleresponse'">
			<div class="card mb-4 text-center border-0">											
			<div class="card-title fw-bold">
				{{ frage.bezeichnung }}
			</div>
			<div class="card-body">
				<div class="btn-group" role="group" aria-label="Evaluierung Antwort Option">
					<template v-for="(antwort, index) in frage.fbFrageAntwort" :key="index">
						<form-input
						  	type="radio"
						  	:label="getIconLabel(antwort.wert)"
						  	:value="antwort.wert"
						  	v-model="selected"
							container-class="btn px-md-4"
						  	class="btn-check"
						>
						</form-input>
				  	</template>
				</div>
			</div>
		</div><!-- .card Fragebogenfrage SingleResponse -->
		</div><!-- .endif Fragebogenfrage SingleResponse-->
	
		<!--  Fragenbogenfrage Text -->
		<div v-if="frage.typ === 'text'">
			<div class="card mb-4 px-0 px-md-1 d-flex text-center border-0">
				<div class="card-body">
					<label class="fw-bold mb-3">{{ frage.bezeichnung }}</label>
					<form-input
					  type="textarea"
					  :placeholder="frage.placeholder"
					  v-model="selected"
					  style="height: 100px"
					/>
					</form-input>
				</div>
			</div><!-- .card Fragebogenfrage Text -->
		</div><!-- .endif Fragebogenfrage Text -->
  	</div>
	`
}