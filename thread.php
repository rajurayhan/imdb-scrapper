<?php
class Task extends Threaded {
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function run() {
        // Execute the task here
        echo "Task {$this->data} executed by thread " . $this->getThreadId() . "\n";
    }
}

class ThreadPool {
    private $threads = array();
    private $queue = array();
    private $size = 0;
    private $running = false;
    
    public function __construct($size) {
        $this->size = $size;
    }
    
    public function addTask($data) {
        $this->queue[] = new Task($data);
    }
    
    public function start() {
        $this->running = true;
        
        for ($i = 0; $i < $this->size; $i++) {
            $this->threads[$i] = new WorkerThread($this->queue);
            $this->threads[$i]->start();
        }
    }
    
    public function wait() {
        $this->running = false;
        
        foreach ($this->threads as $thread) {
            $thread->join();
        }
    }
}

class WorkerThread extends Thread {
    private $queue;
    
    public function __construct(&$queue) {
        $this->queue = &$queue;
    }
    
    public function run() {
        while (true) {
            if (count($this->queue) == 0) {
                if ($this->getThreadPool()->isRunning()) {
                    usleep(1000);
                    continue;
                } else {
                    break;
                }
            }
            
            $task = array_shift($this->queue);
            $task->run();
        }
    }
    
    public function getThreadPool() {
        return $this->getParent();
    }
}

// Create a thread pool with 4 worker threads
$threadPool = new ThreadPool(4);

// Add some tasks to the queue
for ($i = 0; $i < 10; $i++) {
    $threadPool->addTask($i);
}

// Start the thread pool
$threadPool->start();

// Wait for all tasks to complete
$threadPool->wait();
