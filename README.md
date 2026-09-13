# Proyecto Hospitalario — Actividad UML individual

**Estudiante:** Glendi Patricia Campos Orellana · **GitHub:** [Glendi20](https://github.com/Glendi20)
**Módulo oficial:** Pacientes: registro, edición, búsqueda y detalle
**Proceso modelado:** Registro, búsqueda y actualización segura de un paciente

## Contenido del repositorio

```
├── .gitignore                        # Excluye tools/plantuml.jar (herramienta externa, no artefacto)
├── DECLARACION_IA.md                 # Declaración transparente de uso de IA
├── README.md                         # Este archivo
├── docs/
│   ├── Guia_Defensa_Oral.md          # Guía breve para la defensa oral individual
│   ├── matriz_trazabilidad.md        # Matriz Requisito → Diagrama → Elemento
│   └── diagramas/
│       ├── 01_caso_de_uso.puml / .png      # Diagrama de Casos de Uso (fuente editable PlantUML + render)
│       ├── 02_actividad.puml   / .png      # Diagrama de Actividad (fuente editable PlantUML + render)
│       └── 03_secuencia.puml   / .png      # Diagrama de Secuencia (fuente editable PlantUML + render)
└── tools/
    └── plantuml.jar                  # Herramienta local para renderizar los .puml (no versionada)
```

## Cómo editar los diagramas

Los tres diagramas están en formato **PlantUML** (texto plano `.puml`), editable con cualquier editor de
texto o con la extensión "PlantUML" de VS Code. Para regenerar las imágenes PNG tras editar un `.puml`:

```bash
java -jar tools/plantuml.jar -tpng docs/diagramas/01_caso_de_uso.puml docs/diagramas/02_actividad.puml docs/diagramas/03_secuencia.puml
```

(`tools/plantuml.jar` no se versiona en el repositorio; puede descargarse de
https://plantuml.com/download si no está presente localmente.)

## Pendiente antes de la entrega (a completar por la estudiante)

- [ ] Documento final en PDF o DOCX (portada, índice, introducción, desarrollo, conclusión, bibliografía),
      exigido por la guía de la actividad — no incluido en este repositorio.
- [ ] Realizar los commits del repositorio (los commits los realiza personalmente la estudiante).
- [ ] Registrar en la portada del documento la URL del repositorio, rama y etiqueta/commit evaluado, y
      completar la sección de evidencia Git (`git log --oneline`, enlace al commit, árbol de archivos).
- [ ] Verificar que el repositorio esté compartido con el docente.
- [ ] Revisar `DECLARACION_IA.md` y ajustarlo si se usó una herramienta o flujo distinto al descrito.
- [ ] Estudiar `docs/Guia_Defensa_Oral.md` para la defensa oral individual.

## Evidencia Git (a completar)

```
$ git log --oneline
[completar]

$ git ls-files
[completar]
```

Enlace al commit evaluado: `https://github.com/Glendi20/proyecto_Hospitalario_GlendiCampos/commit/[HASH]`
