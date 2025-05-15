export default {
	name: 'Countdown',
	props: {
		duration: Number,
		type: {
			type: String,
			default: 'circle',
			validator: value => ['circle', 'icon'].includes(value)
		}
	},
	data(){
		return {
			timeLeft: this.duration,
			countdownTimer: null
		}
	},
	mounted() {
		this.startCountdown();
	},
	beforeUnmount() {
		clearInterval(this.intervalId);
	},
	watch: {
		timeLeft(newVal) {
			if (newVal === 0) {
				this.endCountdown();
			}
		}
	},
	computed: {
		display() {
			const minutes = String(Math.floor(this.timeLeft / 60)).padStart(2, "0");
			const seconds = String(this.timeLeft % 60).padStart(2, "0");
			return `${minutes}:${seconds}`;
		},
		circleDasharray() {
			const rawTimeFraction = this.timeLeft / this.duration;
			const adjusted = rawTimeFraction - (1 / this.duration) * (1 - rawTimeFraction);
			return `${(adjusted * 283).toFixed(0)} 283`; // 283 = full circle
		},
		colorClass() {
			if (this.timeLeft <= this.duration * 0.25) return "red";
			if (this.timeLeft <= this.duration * 0.5) return "orange";
			return "green";
		}
	},
	methods: {
		startCountdown() {
			// Stop timer if already running
			if (this.countdownTimer) clearInterval(this.countdownTimer);

			// Start a new interval that ticks every second
			this.countdownTimer = setInterval(() => {
				if (this.timeLeft > 0) {
					// Decrease countdown
					this.timeLeft--;
				}
			}, 1000); // Interval every second
		},
		endCountdown() {
			// Stop timer
			clearInterval(this.countdownTimer);

			// Reset timer
			this.countdownTimer = null;

			// Emit method
			this.$emit('countdownEnded');
		}
	},
	template: `
	<!-- type circle renders duration label inside circle -->
	<div v-if="type === 'circle'" class="countdown-container d-flex align-items-center position-fixed top-50 translate-middle-y">
		<div class="position-relative">
			<svg class="countdown__svg" viewBox="0 0 100 100" width="200" height="200">
				<g class="countdown__circle">
					<circle class="countdown__path-elapsed" cx="50" cy="50" r="45" />
					<path
						:stroke-dasharray="circleDasharray"
						class="countdown__path-remaining"
						:class="colorClass"
						d="M 50,50 m 0,-45 a 45,45 0 1,1 0,90 a 45,45 0 1,1 0,-90"
					/>
				</g>
			</svg>
			<span class="countdown__label position-absolute top-50 start-50 translate-middle fs-3 fw-bold">
				{{ display }}
			</span>
		</div>
	</div>
	
	<!-- type icon renders icon with duration label to the right  -->
  	<div v-if="type === 'icon'" class="countdown-container d-flex align-items-center gap-2">
		<i class="fa-solid fa-stopwatch fa-2x" :style="{ color: colorClass }"></i>
		<span class="countdown__label fs-5 fw-bold">{{ display }}</span>
	</div>
	`
};
