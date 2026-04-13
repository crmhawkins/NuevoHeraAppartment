#!/usr/bin/env python3
"""Generate professional PDF reports for Hawkins Suites CRM improvements."""

import re
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm, cm
from reportlab.lib.colors import HexColor, white, black, Color
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_LEFT, TA_CENTER, TA_JUSTIFY, TA_RIGHT
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, PageBreak, Table, TableStyle,
    HRFlowable, KeepTogether, ListFlowable, ListItem
)
from reportlab.pdfgen import canvas
from reportlab.platypus.doctemplate import PageTemplate, BaseDocTemplate, Frame
import os

# Brand colors
TURQUOISE = HexColor('#0891b2')
TURQUOISE_LIGHT = HexColor('#e0f7fa')
TURQUOISE_DARK = HexColor('#0e7490')
DARK_TEXT = HexColor('#1a1a2e')
GREY_TEXT = HexColor('#4a4a5a')
LIGHT_GREY = HexColor('#f1f5f9')
RED_BADGE = HexColor('#dc2626')
YELLOW_BADGE = HexColor('#f59e0b')
GREEN_BADGE = HexColor('#16a34a')

WIDTH, HEIGHT = A4

def create_styles():
    styles = getSampleStyleSheet()

    styles.add(ParagraphStyle(
        'DocTitle', parent=styles['Title'],
        fontSize=26, textColor=white, fontName='Helvetica-Bold',
        alignment=TA_LEFT, spaceAfter=6, leading=32
    ))
    styles.add(ParagraphStyle(
        'DocSubtitle', parent=styles['Normal'],
        fontSize=13, textColor=HexColor('#b0e0e6'), fontName='Helvetica',
        alignment=TA_LEFT, spaceAfter=2
    ))
    styles.add(ParagraphStyle(
        'SectionTitle', parent=styles['Heading1'],
        fontSize=18, textColor=TURQUOISE_DARK, fontName='Helvetica-Bold',
        spaceBefore=20, spaceAfter=8, borderPadding=(0, 0, 4, 0),
    ))
    styles.add(ParagraphStyle(
        'SubSection', parent=styles['Heading2'],
        fontSize=13, textColor=DARK_TEXT, fontName='Helvetica-Bold',
        spaceBefore=12, spaceAfter=4
    ))
    styles.add(ParagraphStyle(
        'BodyText2', parent=styles['Normal'],
        fontSize=10, textColor=GREY_TEXT, fontName='Helvetica',
        alignment=TA_JUSTIFY, spaceAfter=6, leading=14
    ))
    styles.add(ParagraphStyle(
        'BulletItem', parent=styles['Normal'],
        fontSize=10, textColor=GREY_TEXT, fontName='Helvetica',
        leftIndent=15, spaceAfter=3, leading=13, bulletIndent=5
    ))
    styles.add(ParagraphStyle(
        'BoldBullet', parent=styles['Normal'],
        fontSize=10, textColor=DARK_TEXT, fontName='Helvetica-Bold',
        leftIndent=15, spaceAfter=3, leading=13, bulletIndent=5
    ))
    styles.add(ParagraphStyle(
        'BenefitBox', parent=styles['Normal'],
        fontSize=10, textColor=TURQUOISE_DARK, fontName='Helvetica-Oblique',
        leftIndent=10, rightIndent=10, spaceAfter=8, leading=13,
        backColor=TURQUOISE_LIGHT, borderPadding=8
    ))
    styles.add(ParagraphStyle(
        'FooterStyle', parent=styles['Normal'],
        fontSize=8, textColor=HexColor('#999999'), fontName='Helvetica',
        alignment=TA_CENTER
    ))
    styles.add(ParagraphStyle(
        'TOCEntry', parent=styles['Normal'],
        fontSize=11, textColor=TURQUOISE_DARK, fontName='Helvetica',
        spaceBefore=4, spaceAfter=4, leftIndent=10
    ))
    styles.add(ParagraphStyle(
        'Executive', parent=styles['Normal'],
        fontSize=11, textColor=DARK_TEXT, fontName='Helvetica',
        alignment=TA_JUSTIFY, spaceAfter=8, leading=15,
        leftIndent=5, rightIndent=5
    ))
    return styles


class HawkinsPDFTemplate(BaseDocTemplate):
    def __init__(self, filename, **kwargs):
        self.doc_title = kwargs.pop('doc_title', 'Informe')
        self.doc_subtitle = kwargs.pop('doc_subtitle', '')
        super().__init__(filename, **kwargs)

        frame = Frame(
            2*cm, 2*cm, WIDTH - 4*cm, HEIGHT - 4*cm,
            id='normal'
        )
        template = PageTemplate(id='main', frames=frame, onPage=self._draw_page)
        self.addPageTemplates([template])

    def _draw_page(self, canvas_obj, doc):
        canvas_obj.saveState()

        # Header bar
        canvas_obj.setFillColor(TURQUOISE)
        canvas_obj.rect(0, HEIGHT - 18*mm, WIDTH, 18*mm, fill=True, stroke=False)

        # Header text
        canvas_obj.setFillColor(white)
        canvas_obj.setFont('Helvetica-Bold', 10)
        canvas_obj.drawString(2*cm, HEIGHT - 12*mm, 'HAWKINS SUITES')

        canvas_obj.setFont('Helvetica', 8)
        canvas_obj.drawRightString(WIDTH - 2*cm, HEIGHT - 12*mm, self.doc_title)

        # Footer
        canvas_obj.setFillColor(LIGHT_GREY)
        canvas_obj.rect(0, 0, WIDTH, 14*mm, fill=True, stroke=False)

        canvas_obj.setFillColor(HexColor('#999999'))
        canvas_obj.setFont('Helvetica', 7)
        canvas_obj.drawString(2*cm, 5*mm, 'Hawkins Real State SL - Apartamentos Algeciras')
        canvas_obj.drawRightString(WIDTH - 2*cm, 5*mm, f'Pagina {doc.page}')

        # Thin turquoise line under header
        canvas_obj.setStrokeColor(TURQUOISE)
        canvas_obj.setLineWidth(0.5)
        canvas_obj.line(2*cm, HEIGHT - 18.5*mm, WIDTH - 2*cm, HEIGHT - 18.5*mm)

        canvas_obj.restoreState()


def create_cover_page(styles, title, subtitle, date):
    """Create a professional cover page."""
    elements = []
    elements.append(Spacer(1, 60*mm))

    # Turquoise block
    cover_title = Paragraph(
        f'<font color="#0891b2" size="32"><b>HAWKINS</b></font>'
        f'<font color="#0e7490" size="32"> SUITES</font>',
        ParagraphStyle('CoverBrand', fontSize=32, alignment=TA_CENTER, spaceAfter=15)
    )
    elements.append(cover_title)

    # Horizontal line
    elements.append(HRFlowable(width="60%", thickness=2, color=TURQUOISE, spaceAfter=15, spaceBefore=5))

    # Title
    cover_main = Paragraph(
        f'<font size="22" color="#1a1a2e"><b>{title}</b></font>',
        ParagraphStyle('CoverMain', fontSize=22, alignment=TA_CENTER, spaceAfter=10)
    )
    elements.append(cover_main)

    # Subtitle
    cover_sub = Paragraph(
        f'<font size="13" color="#4a4a5a">{subtitle}</font>',
        ParagraphStyle('CoverSub', fontSize=13, alignment=TA_CENTER, spaceAfter=30)
    )
    elements.append(cover_sub)

    elements.append(Spacer(1, 30*mm))

    # Date and author
    elements.append(HRFlowable(width="40%", thickness=1, color=LIGHT_GREY, spaceAfter=10))

    info = Paragraph(
        f'<font size="10" color="#999999">{date}<br/>Equipo de Desarrollo</font>',
        ParagraphStyle('CoverInfo', fontSize=10, alignment=TA_CENTER, textColor=HexColor('#999999'))
    )
    elements.append(info)

    elements.append(PageBreak())
    return elements


def parse_markdown_to_elements(md_text, styles):
    """Parse markdown text into reportlab flowable elements."""
    elements = []
    lines = md_text.split('\n')
    i = 0
    in_list = False

    while i < len(lines):
        line = lines[i].strip()

        # Skip empty lines
        if not line:
            if in_list:
                in_list = False
            i += 1
            continue

        # Skip the main title (already in cover)
        if line.startswith('# ') and i < 5:
            i += 1
            continue

        # Skip metadata lines at top
        if line.startswith('**Fecha:**') or line.startswith('**Preparado por:**') or line.startswith('**Version:**'):
            i += 1
            continue

        # Skip horizontal rules
        if line == '---':
            elements.append(Spacer(1, 5*mm))
            elements.append(HRFlowable(width="100%", thickness=0.5, color=LIGHT_GREY, spaceAfter=5))
            i += 1
            continue

        # Section headers
        if line.startswith('## '):
            title = line[3:].strip()
            title = clean_md(title)
            elements.append(Spacer(1, 5*mm))
            elements.append(Paragraph(title, styles['SectionTitle']))
            elements.append(HRFlowable(width="100%", thickness=1.5, color=TURQUOISE, spaceAfter=8))
            i += 1
            continue

        if line.startswith('### '):
            title = line[4:].strip()
            title = clean_md(title)
            elements.append(Paragraph(title, styles['SubSection']))
            i += 1
            continue

        # Benefit box (lines starting with specific text)
        if 'Beneficio para el negocio' in line or 'beneficio' in line.lower():
            # Collect the benefit text
            benefit_text = ''
            i += 1
            while i < len(lines) and lines[i].strip() and not lines[i].strip().startswith('#') and lines[i].strip() != '---':
                benefit_text += lines[i].strip() + ' '
                i += 1
            if benefit_text.strip():
                elements.append(Paragraph(
                    f'<b>Beneficio:</b> {clean_md(benefit_text.strip())}',
                    styles['BenefitBox']
                ))
            continue

        # Bullet points
        if line.startswith('- '):
            text = line[2:].strip()
            text = format_bold_in_text(text)
            elements.append(Paragraph(
                f'<bullet>&bull;</bullet> {text}',
                styles['BulletItem']
            ))
            i += 1
            continue

        # Numbered items
        if re.match(r'^\d+\.\s', line):
            text = re.sub(r'^\d+\.\s*', '', line).strip()
            text = format_bold_in_text(text)
            num = re.match(r'^(\d+)\.', line).group(1)
            elements.append(Paragraph(
                f'<b>{num}.</b> {text}',
                styles['BulletItem']
            ))
            i += 1
            continue

        # Code blocks - skip
        if line.startswith('```'):
            i += 1
            while i < len(lines) and not lines[i].strip().startswith('```'):
                i += 1
            i += 1
            continue

        # Tables
        if '|' in line and i + 1 < len(lines) and '|' in lines[i + 1]:
            table_lines = []
            while i < len(lines) and '|' in lines[i]:
                if not lines[i].strip().startswith('|--') and not re.match(r'^\|[\s\-\|]+\|$', lines[i].strip()):
                    cells = [c.strip() for c in lines[i].strip().split('|') if c.strip()]
                    if cells:
                        table_lines.append(cells)
                i += 1

            if table_lines:
                # Normalize columns
                max_cols = max(len(row) for row in table_lines)
                for row in table_lines:
                    while len(row) < max_cols:
                        row.append('')

                table = Table(table_lines, repeatRows=1)
                table.setStyle(TableStyle([
                    ('BACKGROUND', (0, 0), (-1, 0), TURQUOISE),
                    ('TEXTCOLOR', (0, 0), (-1, 0), white),
                    ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                    ('FONTSIZE', (0, 0), (-1, -1), 8),
                    ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
                    ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
                    ('GRID', (0, 0), (-1, -1), 0.5, HexColor('#e0e0e0')),
                    ('ROWBACKGROUNDS', (0, 1), (-1, -1), [white, LIGHT_GREY]),
                    ('TOPPADDING', (0, 0), (-1, -1), 4),
                    ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
                    ('LEFTPADDING', (0, 0), (-1, -1), 6),
                ]))
                elements.append(Spacer(1, 3*mm))
                elements.append(table)
                elements.append(Spacer(1, 3*mm))
            continue

        # Regular paragraph
        text = clean_md(line)
        text = format_bold_in_text(text)
        if text:
            elements.append(Paragraph(text, styles['BodyText2']))

        i += 1

    return elements


def clean_md(text):
    """Remove markdown formatting characters for reportlab."""
    text = text.replace('&', '&amp;')
    text = text.replace('<', '&lt;').replace('>', '&gt;')
    text = re.sub(r'`([^`]+)`', r'<font face="Courier" color="#0891b2">\1</font>', text)
    return text


def format_bold_in_text(text):
    """Convert **bold** markdown to reportlab bold tags."""
    # Handle **bold**
    text = re.sub(r'\*\*([^*]+)\*\*', r'<b>\1</b>', text)
    return text


def generate_pdf(md_file, pdf_file, title, subtitle):
    """Generate a professional PDF from a markdown file."""

    with open(md_file, 'r', encoding='utf-8') as f:
        md_content = f.read()

    styles = create_styles()

    doc = HawkinsPDFTemplate(
        pdf_file,
        pagesize=A4,
        topMargin=22*mm,
        bottomMargin=18*mm,
        leftMargin=2*cm,
        rightMargin=2*cm,
        doc_title=title,
        doc_subtitle=subtitle
    )

    elements = []

    # Cover page
    elements.extend(create_cover_page(styles, title, subtitle, '13 de abril de 2026'))

    # Table of contents
    elements.append(Paragraph('Indice', styles['SectionTitle']))
    elements.append(HRFlowable(width="100%", thickness=1.5, color=TURQUOISE, spaceAfter=10))

    # Extract sections for TOC
    for line in md_content.split('\n'):
        line = line.strip()
        if line.startswith('## ') and not line.startswith('## Resumen'):
            section = line[3:].strip()
            section = re.sub(r'[*_`]', '', section)
            elements.append(Paragraph(f'<bullet>&rarr;</bullet> {section}', styles['TOCEntry']))

    elements.append(PageBreak())

    # Parse content
    elements.extend(parse_markdown_to_elements(md_content, styles))

    # Final page
    elements.append(Spacer(1, 30*mm))
    elements.append(HRFlowable(width="60%", thickness=1, color=TURQUOISE, spaceAfter=10, spaceBefore=10))
    elements.append(Paragraph(
        '<font color="#999999" size="9">Documento generado el 13 de abril de 2026<br/>'
        'Hawkins Real State SL - Apartamentos Algeciras<br/>'
        'www.apartamentosalgeciras.com</font>',
        ParagraphStyle('FinalNote', alignment=TA_CENTER, fontSize=9)
    ))

    doc.build(elements)
    print(f'PDF generado: {pdf_file}')


if __name__ == '__main__':
    base = r'D:\proyectos\programasivan\NuevoHeraAppartment'

    generate_pdf(
        os.path.join(base, 'INFORME_MEJORAS_FUNCIONALES.md'),
        os.path.join(base, 'INFORME_MEJORAS_FUNCIONALES.pdf'),
        'Informe de Mejoras Funcionales',
        'CRM Hawkins Suites - Sprint Abril 2026'
    )

    generate_pdf(
        os.path.join(base, 'INFORME_MEJORAS_TECNICAS.md'),
        os.path.join(base, 'INFORME_MEJORAS_TECNICAS.pdf'),
        'Informe de Mejoras Tecnicas',
        'CRM Hawkins Suites - Sprint Abril 2026'
    )

    print('Ambos PDFs generados correctamente.')
