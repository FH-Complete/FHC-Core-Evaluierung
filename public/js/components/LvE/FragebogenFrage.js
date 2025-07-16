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
					{{ frage.bezeichnung_by_language }} {{ frage.verpflichtend ? ' *' : ''}}
				</div>
				<div class="card-body">
					<div 
					class="d-flex justify-content-evenly justify-content-sm-center" 
					role="group" 
					aria-label="Evaluierung Antwort Option"
					>
					<template v-for="(antwort, index) in frage.fbFrageAntwort" :key="index">
						<div class="px-md-2">
						  	<div class="mb-auto">
								<input
								type="radio"				
								:id="'antwort-' + frage.lvevaluierung_frage_id + '-' + index"
								:value="antwort.wert"
								:checked="lvevaluierung_frage_antwort_id == antwort.wert"
								 @click="$emit('update:lvevaluierung_frage_antwort_id',
								  	lvevaluierung_frage_antwort_id == antwort.wert 
										? null 
										: antwort.wert
									)"
								container-class="btn px-md-4"
								class="btn-check antwort-radio-btn"
							></div>
						  	<div class="px-1">
								<label
									class="antwort-label d-flex flex-column mx-2 mx-sm-3 mx-md-4"
									:for="'antwort-' + frage.lvevaluierung_frage_id + '-' + index"
									>
									<i
										:class="[
											(lvevaluierung_frage_antwort_id == antwort.wert ? 'fa-solid' : 'fa-regular'),
											'fa-' + getIcon(antwort.wert),
											'fa-2x',
											'antwort-icon',
											'wert-' + antwort.wert,
											lvevaluierung_frage_antwort_id == antwort.wert ? 'antwort-checked' : ''
										  ]"
										  aria-hidden="true"
									></i>
									<span class="visually-hidden">{{ antwort.wert }}</span><!-- screen-reader-accessible label-->
								</label>
							</div>
						  	<div class="antwort-text-wrapper mt-1">
						  		<span class="small text-muted text-wrap">{{antwort.bezeichnung_by_language}}</span>
							</div>
						</div>
				  	</template>
				</div>
			</div>
			</div><!-- .card Fragebogenfrage SingleResponse -->
		</div><!-- .endif Fragebogenfrage SingleResponse-->
	
		<!--  Fragenbogenfrage Text -->
		<div v-if="frage.typ === 'text'">
			<div class="card mb-4 px-0 px-md-1 d-flex text-center border-0">
				<div class="card-body">
					<label class="fw-bold mb-3">{{ frage.bezeichnung_by_language }} {{ frage.verpflichtend ? ' *' : ''}}</label>
					<form-input
				  		type="textarea"
					  	:placeholder="frage.placeholder_by_language"
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