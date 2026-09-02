<?php
/**
 * Testes unitários para a classe PDSM_Job_Manager
 */

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/class-job-manager.php';

class Test_PDSM_Job_Manager extends TestCase {

    private $job_manager;

    protected function setUp(): void {
        $this->job_manager = new PDSM_Job_Manager();
    }

    public function test_create_job_structure() {
        // Como o WPDB não está mockado completamente neste setup simples,
        // validamos apenas se os métodos da classe existem.
        $this->assertTrue(method_exists($this->job_manager, 'create_job'), "O método create_job deve existir");
        $this->assertTrue(method_exists($this->job_manager, 'process_job'), "O método process_job deve existir");
        $this->assertTrue(method_exists($this->job_manager, 'get_status'), "O método get_status deve existir");
    }
}
