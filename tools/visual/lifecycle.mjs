export class VisualRunLifecycle {
    constructor(stopContainer, signals = process, gracePeriod = 15_000) {
        this.stopContainer = stopContainer
        this.signals = signals
        this.gracePeriod = gracePeriod
        this.interrupt = () => {
            const repeated = this.interrupted
            this.interrupted = true
            if (repeated) this.forceStop()
            else if (this.isAlive(this.child) || this.isAlive(this.startupChild)) {
                // Playwright gets graceful fixture teardown. In-flight Docker
                // commands are cancelled too, with the same bounded escalation.
                if (this.isAlive(this.child)) this.child.kill('SIGINT')
                else this.startupChild.kill('SIGTERM')
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
        if (this.isAlive(this.child)) this.child.kill('SIGKILL')
        if (this.isAlive(this.startupChild)) this.startupChild.kill('SIGKILL')
        // The owner awaits the Docker command before final cleanup, including
        // commands cancelled before Docker has returned the container ID.
        if (!this.isAlive(this.startupChild)) void this.stop()
    }

    isAlive(child) {
        return child?.exitCode === null && child?.signalCode === null
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
        clearTimeout(this.timer)
        try {
            await this.stop()
            if (this.cleanupError) throw this.cleanupError
        } finally {
            for (const signal of ['SIGINT', 'SIGTERM']) this.signals.off(signal, this.interrupt)
        }
    }
}
