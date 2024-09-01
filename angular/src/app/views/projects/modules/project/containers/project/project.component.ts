import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject } from 'rxjs';
import { skip, takeUntil } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { SharedProjectService } from 'src/app/shared/services/shared-project.service';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';
import { ProjectService } from 'src/app/views/projects/modules/project/project.service';

@Component({
  selector: 'oz-finance-project',
  templateUrl: './project.component.html',
  styleUrls: ['./project.component.scss'],
})
export class ProjectComponent implements OnInit, OnDestroy {
  public heading: string;
  public purchaseOrderProject = false;

  private project: Project;
  private onDestroy$: Subject<void> = new Subject<void>();

  public constructor(
    private route: ActivatedRoute,
    private router: Router,
    private projectService: ProjectService,
    private globalService: GlobalService,
    private sharedProjectService: SharedProjectService,
    private tablePreferencesService: TablePreferencesService
  ) {}

  public ngOnInit(): void {
    this.getResolvedData();
    this.initSubscriptions();
    this.setValues();
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
  }

  private getResolvedData(): void {
    this.project = this.route.snapshot.data.project;
    this.purchaseOrderProject = this.project.purchase_order_project;
  }

  private initSubscriptions(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(() => {
        this.tablePreferencesService.removeTablePage(2);
        this.router.navigate(['/']).then();
      });

    this.sharedProjectService.project
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(val => {
        this.project = val;
      });
  }

  private setValues(): void {
    this.heading = this.purchaseOrderProject
      ? `Purchase Orders of : ${this.project.name}`
      : this.project?.order
        ? `Order: ${this.project.order.number}`
        : `Quote: ${this.project.quotes?.rows?.data[0]?.number}`;

    if (this.project?.name) {
      this.heading = this.project.name;
    }
  }
}
