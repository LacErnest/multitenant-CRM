import {
  Component,
  EventEmitter,
  OnInit,
  Output,
  TemplateRef,
  ViewChild,
} from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { Observable, of, Subject } from 'rxjs';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  filter,
  map,
  switchMap,
  tap,
} from 'rxjs/operators';
import { ModalComponent } from 'src/app/shared/components/modal/modal.component';
import { SearchEntity } from 'src/app/shared/interfaces/search-entity';
import { CompanyLegalEntitiesService } from '../../company-legal-entities.service';

@Component({
  selector: 'oz-finance-add-legal-entity-modal',
  templateUrl: './add-legal-entity-modal.component.html',
  styleUrls: ['./add-legal-entity-modal.component.scss'],
})
export class AddLegalEntityModalComponent implements OnInit {
  @Output() public legalEntityChosen: EventEmitter<string> =
    new EventEmitter<string>();

  @ViewChild('modal') public modal: ModalComponent;
  @ViewChild('form') public form: TemplateRef<any>;

  public legalEntityForm: FormGroup;
  public isLoading = false;

  public legalEntitySelect$: Observable<SearchEntity[]>;
  public legalEntityInput$: Subject<string> = new Subject<string>();
  public legalEntityLoading = false;

  public constructor(
    private companyLegalEntitiesService: CompanyLegalEntitiesService,
    private fb: FormBuilder
  ) {}

  public get isLegalEntityFormDisabled(): boolean {
    return (
      this.legalEntityForm.invalid ||
      !this.legalEntityForm.dirty ||
      this.isLoading ||
      this.legalEntityLoading
    );
  }

  public ngOnInit(): void {
    this.initLegalEntityForm();
    this.initLegalEntityTypeAhead();
  }

  public openLegalEntityModal(): void {
    this.modal.openModal().subscribe(
      () => this.addLegalEntityToCompany(),
      () => {},
      () => this.legalEntityForm.reset()
    );
  }

  private addLegalEntityToCompany(): void {
    this.legalEntityChosen.emit(
      this.legalEntityForm.get('legal_entity_id').value
    );
  }

  private initLegalEntityForm(): void {
    this.legalEntityForm = this.fb.group({
      legal_entity_id: new FormControl(undefined, Validators.required),
    });
  }

  private initLegalEntityTypeAhead(): void {
    this.legalEntitySelect$ = this.legalEntityInput$.pipe(
      filter(l => !!l),
      debounceTime(500),
      distinctUntilChanged(),
      tap(() => (this.legalEntityLoading = true)),
      switchMap(term => {
        return this.companyLegalEntitiesService.suggestLegalEntity(term).pipe(
          map(res => res.suggestions),
          catchError(() => of([])), // empty list on error
          tap(() => (this.legalEntityLoading = false))
        );
      })
    );
  }
}
