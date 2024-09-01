import {
  animate,
  style,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import {
  Component,
  ElementRef,
  HostListener,
  Input,
  OnChanges,
  OnDestroy,
  OnInit,
  Renderer2,
  SimpleChanges,
  ViewChild,
} from '@angular/core';
import {
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
  menuEnterAnimation,
  menuLeaveAnimation,
} from 'src/app/shared/animations/browser-animations';
import { AngularEditorConfig } from '@kolkov/angular-editor';
import { FormControl } from '@angular/forms';

@Component({
  selector: 'oz-finance-html-editor',
  templateUrl: './html-editor.component.html',
  styleUrls: ['./html-editor.component.scss'],
  animations: [
    trigger('slideOverAnimation', [
      transition(':enter', [
        style({ transform: 'translateX(100%)' }),
        animate('500ms ease-in-out', style({ transform: 'translateX(0)' })),
      ]),
      transition(':leave', [
        style({ transform: 'translateX(0)' }),
        animate('500ms ease-in-out', style({ transform: 'translateX(100%)' })),
      ]),
    ]),
    trigger('menuAnimation', [
      transition(':enter', useAnimation(menuEnterAnimation)),
      transition(':leave', useAnimation(menuLeaveAnimation)),
    ]),
    trigger('detailAnimation', [
      transition(':enter', useAnimation(displayAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class HtmlEditorComponent implements OnInit, OnDestroy, OnChanges {
  @Input() control: FormControl;
  @Input() disabled = false;

  public html = '';
  public isModalVisible = false;

  editorConfig: AngularEditorConfig = {
    editable: true,
    spellcheck: true,
    height: 'auto',
    minHeight: '200px',
    maxHeight: 'auto',
    width: 'auto',
    minWidth: '0',
    translate: 'yes',
    enableToolbar: true,
    showToolbar: true,
    placeholder: 'Enter text here...',
    defaultParagraphSeparator: '',
    defaultFontName: '',
    defaultFontSize: '',
    fonts: [
      { class: 'arial', name: 'Arial' },
      { class: 'times-new-roman', name: 'Times New Roman' },
      { class: 'calibri', name: 'Calibri' },
      { class: 'comic-sans-ms', name: 'Comic Sans MS' },
    ],
    customClasses: [
      {
        name: 'quote',
        class: 'quote',
      },
      {
        name: 'redText',
        class: 'redText',
      },
      {
        name: 'titleText',
        class: 'titleText',
        tag: 'h1',
      },
    ],
    sanitize: true,
    toolbarPosition: 'top',
    toolbarHiddenButtons: [],
  };

  @ViewChild('modalElement') modalElement: ElementRef;
  private stopMouseMoveListener: any = null;
  private stopMouseUpListener: any = null;

  constructor(
    private elRef: ElementRef,
    private renderer: Renderer2
  ) {}

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent) {
    const clickedInside = this.elRef.nativeElement.contains(event.target);
    if (!clickedInside) {
      this.isModalVisible = false;
    }
  }

  ngOnInit(): void {
    //
  }

  // make sure to destory the editor
  ngOnDestroy(): void {
    //
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['disabled']) {
      this.toogleEditorState();
    }
  }

  onChange(html: string): void {
    this.html = html;
    // Handle the change event
  }

  toogleEditorState(): void {
    this.editorConfig.editable = !this.disabled;
  }

  toggleModal(): void {
    this.isModalVisible = !this.isModalVisible;
  }

  onBodyClick(): void {
    if (this.isModalVisible) {
      this.isModalVisible = false;
    }
  }

  initResize(event: MouseEvent): void {
    event.preventDefault();
    this.stopMouseMoveListener = this.renderer.listen(
      'window',
      'mousemove',
      e => this.resize(e)
    );
    this.stopMouseUpListener = this.renderer.listen('window', 'mouseup', () =>
      this.stopResize()
    );
  }

  resize(event: MouseEvent): void {
    const modal = this.modalElement.nativeElement;
    const width = window.innerWidth - event.clientX;
    if (width > 300) {
      // Ensure minimum width
      this.renderer.setStyle(modal, 'width', `${width}px`);
    }
  }

  stopResize(): void {
    // Stop listening to the mousemove and mouseup events
    if (this.stopMouseMoveListener) {
      this.stopMouseMoveListener();
      this.stopMouseMoveListener = null;
    }
    if (this.stopMouseUpListener) {
      this.stopMouseUpListener();
      this.stopMouseUpListener = null;
    }
  }
}
