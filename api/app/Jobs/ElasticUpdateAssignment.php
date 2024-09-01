<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Contact;
use App\Models\CreditNote;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Project;
use App\Models\Customer;
use App\Models\MasterShadow;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Resource;
use App\Models\User;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tenancy\Facades\Tenancy;

class ElasticUpdateAssignment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $tenant_key;
    protected string $model;
    protected string $modelId;

    /**
     * Create a new job instance.
     *
     * @param string $tenant_key
     * @param string $model
     * @param string $modelId
     */
    public function __construct(string $tenant_key, string $model, string $modelId)
    {
        $this->tenant_key = $tenant_key;
        $this->model = $model;
        $this->modelId = $modelId;
        $this->setExceptionHandler();
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws LimiterTimeoutException
     */
    public function handle()
    {
        Redis::funnel($this->tenant_key)->limit(1)->then(function () {
            if ($this->model == PurchaseOrder::class) {
                $po = PurchaseOrder::with('project')->find($this->modelId);

                if ($po) {
                    $po->updateIndex();

                    if (!$po->project->purchase_order_project) {
                        $po->project->order->updateIndex();
                        $quotes = $po->project->quotes;
                        $quotes->each(function ($quote) {
                            $quote->updateIndex();
                        });

                        $invoices = $po->project->invoices;
                        $invoices->each(function ($invoice) {
                            $invoice->updateIndex();
                        });
                        $purchaseOrders = $po->project->purchaseOrders;
                        $purchaseOrders->each(function ($purchaseOrder) {
                            $purchaseOrder->updateIndex();
                        });
                        $order = $po->project->order;

                        if (!empty($order->shadow)) {
                                $this->updateMasterEntities($order->id, 'order');
                        }
                    }
                }
            }

            if ($this->model == Resource::class) {
                $resource = Resource::with('purchaseOrders')->find($this->modelId);
                if ($resource) {
                    $resource->purchaseOrders->each(function ($purchaseOrder) {
                        $purchaseOrder->updateIndex();
                    });
                }
            }

            if ($this->model == Quote::class) {
                $quote = Quote::with('project')->find($this->modelId);
                if ($quote) {
                    $quotes = $quote->project->quotes;
                    $quotes->each(function ($quote) {
                        $quote->updateIndex();
                    });
                    $quote->project->updateIndex();
                    $invoices = $quote->project->invoices;
                    $invoices->each(function ($invoice) {
                          $invoice->updateIndex();
                    });
                }
            }

            if ($this->model == Customer::class) {
                $customer = Customer::find($this->modelId);

                if (!empty($customer->contact->projects->invoices)) {
                    $invoices = $customer->contact->projects->invoices;

                    $invoices->each(function ($invoice) {
                        $invoice->updateIndex();
                    });
                }
            }

            if ($this->model == Invoice::class) {
                $invoice = Invoice::find($this->modelId);
                if ($invoice) {
                    if (!$invoice->project->purchase_order_project) {
                        $invoice->order->updateIndex();
                        $invoices = $invoice->project->invoices;
                        $invoices->each(function ($invoice) {
                            $invoice->updateIndex();
                        });
                          $quotes = $invoice->project->quotes;
                          $quotes->each(function ($quote) {
                              $quote->updateIndex();
                          });
                    }

                    if (!empty($invoice->shadow)) {
                        $this->updateMasterEntities($invoice->id, 'invoice');
                    }
                }
            }

            if ($this->model == Order::class) {
                $order = Order::find($this->modelId);
                if ($order) {
                    $order->updateIndex();
                    $quotes = $order->project->quotes;
                    $quotes->each(function ($quote) {
                        $quote->updateIndex();
                    });
                    $invoices = $order->invoices;
                    $invoices->each(function ($invoice) {
                          $invoice->updateIndex();

                        if (!empty($invoice->shadow)) {
                            $this->updateMasterEntities($invoice->id, 'invoice');
                        }
                    });

                    if (!empty($order->shadow)) {
                          $this->updateMasterEntities($order->id, 'order');
                    }
                }
            }

            if ($this->model == User::class) {
                $user = User::with('saleProjects')->find($this->modelId);
                if ($user) {
                    $projects = $user->saleProjects;
                    $projects->each(function ($project) {
                        $project->updateIndex();
                    });
                }
            }

            if ($this->model == Employee::class) {
                $employee = Employee::with(['projectManagers', 'projectManagers.order', 'purchaseOrders'])->find($this->modelId);
                if ($employee) {
                    $projects = $employee->projectManagers;
                    $projects->each(function ($project) {
                        $project->updateIndex();
                        $order = $project->order;
                        if ($order) {
                            $order->updateIndex();

                            if (!empty($order->shadow)) {
                                $this->updateMasterEntities($order->id, 'order');
                            }
                        }
                    });

                    $employee->purchaseOrders->each(function ($purchaseOrder) {
                          $purchaseOrder->updateIndex();
                    });
                }
            }

            if ($this->model == Contact::class) {
                $contact = Contact::with('projects')->find($this->modelId);
                if ($contact) {
                    $projects = $contact->projects;
                    $projects->each(function ($project) {
                        $project->updateIndex();
                    });
                }
            }

            if ($this->model == Project::class) {
                $project = Project::with('quotes', 'order', 'purchaseOrders', 'invoices')->find($this->modelId);
                if ($project) {
                    $project->updateIndex();
                    $quotes = $project->quotes;
                    $quotes->each(function ($quote) {
                        $quote->updateIndex();
                    });
                    $order = $project->order;

                    if ($order) {
                          $order->updateIndex();

                        if (!empty($order->shadow)) {
                            $this->updateMasterEntities($order->id, 'order');
                        }
                    }
                    $invoices = $project->invoices;
                    $invoices->each(function ($invoice) {
                        $invoice->updateIndex();

                        if (!empty($invoice->shadow)) {
                            $this->updateMasterEntities($invoice->id, 'invoice');
                        }
                    });
                    $purchaseOrders = $project->purchaseOrders;
                    $purchaseOrders->each(function ($purchaseOrder) {
                          $purchaseOrder->updateIndex();
                    });
                }
            }

            if ($this->model == CreditNote::class) {
                $creditNote = CreditNote::with('invoice')->find($this->modelId);
                if ($creditNote) {
                    $invoice = $creditNote->invoice;
                    if ($invoice) {
                        $invoice->updateIndex();
                    }
                }
            }
        }, function () {
            $this->release(5);
        });
    }

    private function updateMasterEntities(string $shadowId, string $entityType):void
    {
        $company = Company::find(getTenantWithConnection());
        $expectedEntityTypes = [
          'order',
          'invoice'
        ];

        if (empty($company) || !in_array($entityType, $expectedEntityTypes)) {
            return;
        }

        $shadows = MasterShadow::where([
          'shadow_id' => $shadowId,
          'shadow_company_id' => $company->id,
        ])->get();

        foreach ($shadows as $shadow) {
            $masterCompany = Company::find($shadow->master_company_id);
            Tenancy::setTenant($masterCompany);
            $modelClass = "\\App\\Models\\$entityType";
            $masterEntity = $modelClass::find($shadow->master_id);

            if ($masterEntity) {
                $masterEntity->updateIndex();
            }
        }

        Tenancy::setTenant($company);
    }

    private function setExceptionHandler()
    {
        set_exception_handler(function ($e) {
            Log::error($e->getMessage());
        });
    }
}
