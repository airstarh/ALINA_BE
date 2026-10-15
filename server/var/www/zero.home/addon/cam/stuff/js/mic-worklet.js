class AlinaCameraPcmProcessor extends AudioWorkletProcessor {
    constructor(options) {
        super();
        this.targetSampleRate = options.processorOptions?.targetSampleRate || 8000;
        this.frameSamples = options.processorOptions?.frameSamples || 320;
        this.ratio = this.targetSampleRate / sampleRate;
        this.phase = 0;
        this.samples = [];
        this.active = true;
        this.port.onmessage = ({data}) => { if (data === 'stop') this.active = false; };
    }

    process(inputs) {
        if (!this.active) return false;
        const channel = inputs[0]?.[0];
        if (!channel) return true;
        for (const sample of channel) {
            this.phase += this.ratio;
            if (this.phase < 1) continue;
            this.phase -= 1;
            const limited = Math.max(-1, Math.min(1, sample));
            this.samples.push(limited < 0 ? Math.round(limited * 32768) : Math.round(limited * 32767));
            if (this.samples.length === this.frameSamples) {
                const pcm = Int16Array.from(this.samples);
                this.samples = [];
                this.port.postMessage(pcm.buffer, [pcm.buffer]);
            }
        }
        return true;
    }
}

registerProcessor('alina-cam-pcm', AlinaCameraPcmProcessor);
