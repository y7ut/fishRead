<?php
namespace FishRead\Site;
use FishRead\Spider\YooReadSpider;
use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

class YooRead extends Command {
  /**
   * 构造函数
   */
  public function __construct(){
    parent:: __construct();
  }
  protected function configure()
  {
    $this
      ->setName('book:yooread')
      ->setDescription('悠读文学专用')
      ->setHelp('输入你想看的书的Url吧！')
      ->addArgument(
        'url',
        InputArgument::REQUIRED,
        'ヾ(๑╹◡╹)ﾉ"输入Url'
      );
  }
  protected function execute(InputInterface $input, OutputInterface $output){
    $url = $input->getArgument('url');
    YooReadSpider::makeBook($url);
  }
}
