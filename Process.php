<?php
$count = 0;
$progressCount = 10;     // 创建子进总量
$childList = array();    // 子进程pid数组
$workProgressCount = 0; // 当前正在执行任务的进程数
$parent_pid = posix_getpid();

echo '父进程: '.$parent_pid."\n";
cli_set_process_title('tyler: master process');

//信号处理函数
function sig_handler($signo)
{
	global $parent_pid;
	global $workProgressCount;
	switch ($signo) {
	case SIGUSR1:
		echo "收到父进程任务,开始执行任务、、、\n";
		posix_kill($parent_pid, SIGUSR2);
		break;
	 case SIGUSR2:
		echo "子进程任务执行完成、、、\n";		
		echo $workProgressCount."\n";
		$workProgressCount--;
		break;
	default:
		break;
	}
}

// 父进程处理收到的 SIGUSR2 信号
pcntl_signal(SIGUSR2,"sig_handler");

// 子进程处理收到的 SIGUSR1 信号
pcntl_signal(SIGUSR1, "sig_handler");

while(true){
	if($count < $progressCount){
		$pid = pcntl_fork();
		$count++;
		if($pid == -1){
			$count--;
			die('could not fork');
		}else if($pid){
			$childList[$pid] = false; //存储子进程pid信息
		}else{
			echo '创建子进程：'. posix_getpid(). "\n";
			cli_set_process_title('tyler: worker process');			
			while(true){
				// 子进程接受父进程任务，处理完后通知父进程
				pcntl_signal_dispatch(); // 接收信号,执行任务      
				sleep(1);
			}
		}  
	}else{
		// 所有子进程都执行完任务,重新给所有子进程分配任务
		if($workProgressCount == 0){
		    foreach($childList as $child_pid => $val){          
				//$childList[$child_pid] = true;
				$workProgressCount++;  //添加工作的进程数
				posix_kill($child_pid, SIGUSR1); // 向 $child_pid 进程发送 SIGUSR1					
                sleep(1); 				
		    }          
		} 
	}	
    // 接收子进程执行完任务消息,更新工作进程    
	pcntl_signal_dispatch(); // 接收子进程任务完成信号	
    // 保持恒定子进程数
    $childPid = pcntl_wait($status,WNOHANG);
    if ($childPid > 0){
        echo $childPid." 子进程退出\n";
        unset($childList[$childPid]);
        $count--;
    }
	echo '当前执行任务进程数: '.$workProgressCount."\n"; 
	sleep(1);  
}




/*
echo '初始化';
$pid = pcntl_fork();
//父进程和子进程都会执行下面代码
if ($pid == -1) {
    //错误处理：创建子进程失败时返回-1.
     die('could not fork');
} else if ($pid) {
     echo '父进程  ';
     //父进程会得到子进程号，所以这里是父进程执行的逻辑
     pcntl_wait($status); //等待子进程中断，防止子进程成为僵尸进程。
} else {
     echo $fpid;
     echo '子进程  ';
     //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
}
*/
