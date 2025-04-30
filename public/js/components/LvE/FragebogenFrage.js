import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";

export default {
	components: {
		FormForm,
		FormInput
	},
	props: {
		frage: Object,
		lvevaluierung_frage_id: Number,
		lvevaluierung_frage_antwort_id: null,
		antwort: null,
	},
	emits: [
		'update:lvevaluierung_frage_id',
		'update:lvevaluierung_frage_antwort_id',
		'update:antwort'
	],
	created() {
		this.$emit('update:lvevaluierung_frage_id', this.frage.lvevaluierung_frage_id)
	},
	methods: {
		getIconLabel(antwortWert, isActive) {
			const style = isActive ? 'fa-solid' : 'fa-regular';
			return '<i class="'+ style + ' fa-' + this.getIcon(antwortWert) + ' fa-2x" style="cursor: pointer"></i>';
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
				{{ frage.bezeichnung_by_language }}
			</div>
			<div class="card-body">
				<div class="btn-group" role="group" aria-label="Evaluierung Antwort Option">
					<template v-for="(antwort, index) in frage.fbFrageAntwort" :key="index">
						<form-input
						  	type="radio"
						  	:label="getIconLabel(antwort.wert, lvevaluierung_frage_antwort_id == antwort.wert)"
						  	:value="antwort.wert"
						  	 @input="$emit('update:lvevaluierung_frage_antwort_id', $event.target.value)"
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
					<label class="fw-bold mb-3">{{ frage.bezeichnung_by_language }}</label>
					<form-input
				  		type="textarea"
					  	:placeholder="frage.placeholder"
						@input="$emit('update:antwort', $event.target.value)"
					  	style="height: 100px"
					/>
					</form-input>
				</div>
			</div><!-- .card Fragebogenfrage Text -->
		</div><!-- .endif Fragebogenfrage Text -->
  	</div>
	`
}