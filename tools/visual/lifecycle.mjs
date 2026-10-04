export class VisualRunLifecycle {
    constructor(stopContainer, signals = process, gracePeriod = 15_000) {
        this.stopContainer = stopContainer
        this.signals = signals
        this.gracePeriod = gracePeriod
        this.interrupt = () => {
            const repeated = this.interrupted
            this.interrupted = true
            if (repeated) this.forceStop()
            else if (this.child) {
                // Allow Playwright to tear down fixtures and its owned server.
                this.child.kill('SIGINT')
                this.timer = setTimeout(() => this.forceStop(), this.gracePeriod)
                this.timer.unref()
            } else void this.stop()
        }
        for (const signal of ['SIGINT', 'SIGTERM']) signals.on(signal, this.interrupt)
    }

    assertRunning() {
        if (this.interrupted) throw new Error('Visual run interrupted.')
    }

    forceStop() {
        if (this.child?.exitCode === null && this.child?.signalCode === null) this.child.kill('SIGKILL')
        void this.stop()
    }

    stop() {
        if (!this.stopping && this.container) {
            // Signal handlers must not create an unhandled rejection. Report
            // cleanup failure to the runner when it awaits disposal instead.
            this.stopping = this.stopContainer(this.container).catch((error) => { this.cleanupError = error })
        }
        return this.stopping ?? Promise.resolve()
    }

    async dispose() {
        for (const signal of ['SIGINT', 'SIGTERM']) this.signals.off(signal, this.interrupt)
        clearTimeout(this.timer)
        await this.stop()
        if (this.cleanupError) throw this.cleanupError
    }
}
