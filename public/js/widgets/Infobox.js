/**
 * This is a responsive Infobox with a default info icon followed by given text (simple string or html).
 * Default is expanded infobox across all devices.
 * By passing collapseBreakpoint, the infobox will collapse for smaller devices.
 * **/
export default {
	name: 'Infobox',
	props: {
		text: {
			type: String,
			required: true
		},
		showInfoIcon: {
			type: Boolean,
			default: true
		},
		collapseBreakpoint: {
			type: String,
			default: '',
			validator: val => ['', 'all', 'sm', 'md', 'lg', 'xl', 'xxl'].includes(val) // Bootstrap5 Breakpoint classes
		}
	},
	data() {
		return {
			collapseId: 'collapseInfoBox_' + Math.random().toString(36).slice(2, 9)
		};
	},
	template: `
    <div>
      	<!-- If collapse breakpoint is given: Collapsed version -->
      	<template v-if="collapseBreakpoint">
      		<div v-if="collapseBreakpoint === 'all'">
				<!-- Collapsed on all devices -->
				<div class="mb-1 text-dark small">
					<i v-if="showInfoIcon" class="fa-solid fa-circle-info text-primary me-2" aria-hidden="true"></i>
					<a 
						:data-bs-toggle="'collapse'"
						:href="'#' + collapseId" 
						role="button" aria-expanded="false" 
						:aria-controls="collapseId"
					>Mehr Infos anzeigen</a>
				</div>
				<div class="collapse mt-2" :id="collapseId">
					<div class="text-muted small" v-html="text"></div>
				</div>
			</div>
		  	<div v-else>
				<!-- Collapse for < collapseBreakpoint -->
				<div :class="'d-' + collapseBreakpoint + '-none'">
					<div class="mb-1 text-dark small">
						<i v-if="showInfoIcon" class="fa-solid fa-circle-info text-primary me-2" aria-hidden="true"></i>
						<a 
							:data-bs-toggle="'collapse'"
							:href="'#' + collapseId" 
							role="button" aria-expanded="false" 
							:aria-controls="collapseId"
						>Mehr Infos anzeigen <!-- todo: english -->
						</a>
					</div>
					<div class="collapse mt-2" :id="collapseId">
						<div class="text-muted small" v-html="text"></div>
					</div>
				</div>
				<!-- Expand for >= collapseBreakpoint -->
				<div :class="'d-none d-' + collapseBreakpoint + '-block'">
    			<div class="text-muted small">
					<i v-if="showInfoIcon" class="fa-solid fa-circle-info text-primary me-2" aria-hidden="true"></i>
					<span v-html="text"></span>
				</div>
			</div>
			</div>
	  	</template>

	  	<!-- Default: Always expanded -->
	  	<template v-else>
			<div class="text-muted small">
    			<i v-if="showInfoIcon" class="fa-solid fa-circle-info text-primary me-2" aria-hidden="true"></i>
    			<span v-html="text"></span>
			</div>
	  	</template>
    </div>
  `
};
