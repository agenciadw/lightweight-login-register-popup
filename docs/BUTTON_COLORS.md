# Personalização de Cores dos Botões

## Visão Geral

A partir da versão 1.3.1, o plugin permite personalizar as cores de cada tipo de botão individualmente, oferecendo controle total sobre a aparência visual do popup de login.

## Botões Disponíveis para Personalização

### 1. Botão Continuar
- **Descrição**: Botão principal "Continuar" na tela inicial
- **Elemento**: `#llrp-email-submit`
- **Configurações**:
  - Cor de Fundo
  - Cor de Fundo (Hover)
  - Cor da Borda
  - Cor da Borda (Hover)
  - Cor do Texto
  - Cor do Texto (Hover)
- **Padrões**:
  - Fundo: `#385b02` (verde escuro)
  - Fundo Hover: `#91b381` (verde claro)

---

### 2. Botão Pular para Checkout
- **Descrição**: Botão "Pular para o checkout" (aparece quando checkout de convidado está ativo)
- **Elementos**: `#llrp-skip-to-checkout`, `.llrp-skip-button`
- **Configurações**:
  - Cor de Fundo
  - Cor de Fundo (Hover)
  - Cor da Borda
  - Cor da Borda (Hover)
  - Cor do Texto
  - Cor do Texto (Hover)
- **Padrões**:
  - Fundo: `#6c757d` (cinza)
  - Fundo Hover: `#5a6268` (cinza escuro)

---

### 3. Botão Google
- **Descrição**: Botão "Continuar com Google"
- **Elementos**: `.llrp-google-button`
- **Configurações**:
  - Cor de Fundo
  - Cor de Fundo (Hover)
  - Cor da Borda
  - Cor da Borda (Hover)
  - Cor do Texto
  - Cor do Texto (Hover)
- **Padrões**:
  - Fundo: `#ffffff` (branco)
  - Fundo Hover: `#f8f9fa` (cinza muito claro)
  - Borda: `#dadce0` (cinza claro)
  - Texto: `#3c4043` (cinza escuro)

**Nota**: O ícone do Google mantém suas cores oficiais independentemente da configuração.

---

### 4. Botão Facebook
- **Descrição**: Botão "Continuar com Facebook"
- **Elementos**: `.llrp-facebook-button`
- **Configurações**:
  - Cor de Fundo
  - Cor de Fundo (Hover)
  - Cor da Borda
  - Cor da Borda (Hover)
  - Cor do Texto
  - Cor do Texto (Hover)
- **Padrões**:
  - Fundo: `#1877f2` (azul Facebook)
  - Fundo Hover: `#166fe5` (azul Facebook escuro)
  - Texto: `#ffffff` (branco)

**Nota**: O ícone do Facebook mantém sua cor oficial independentemente da configuração.

---

### 5. Botão de Código (WhatsApp/E-mail)
- **Descrição**: Botões de envio de código por WhatsApp ou E-mail
- **Elementos**: `#llrp-send-code`
- **Configurações**:
  - Cor de Fundo
  - Cor de Fundo (Hover)
  - Cor da Borda
  - Cor da Borda (Hover)
  - Cor do Texto
  - Cor do Texto (Hover)
- **Padrões**:
  - Fundo: `#2271b1` (azul)
  - Fundo Hover: `#1e639a` (azul escuro)
  - Texto: `#ffffff` (branco)

---

## Como Configurar

### Passo 1: Acessar o Painel de Configuração

1. Acesse o WordPress Admin
2. Vá em **WooCommerce** → **Login Popup**
3. Clique na aba **Cores**
4. Role até a seção do botão que deseja personalizar

### Passo 2: Escolher as Cores

Cada botão possui 6 opções de cor:

1. **Fundo**: Cor de fundo do botão em estado normal
2. **Fundo (Hover)**: Cor de fundo quando o mouse passa sobre o botão
3. **Borda**: Cor da borda em estado normal
4. **Borda (Hover)**: Cor da borda quando o mouse passa sobre o botão
5. **Texto**: Cor do texto em estado normal
6. **Texto (Hover)**: Cor do texto quando o mouse passa sobre o botão

### Passo 3: Usar o Color Picker

- Clique no campo de cor para abrir o seletor
- Escolha a cor desejada
- O código HEX será preenchido automaticamente
- Ou digite manualmente o código HEX (ex: `#385b02`)

### Passo 4: Salvar

- Role até o final da página
- Clique em **Salvar Configurações**
- As cores serão aplicadas imediatamente

---

## Exemplos de Configuração

### Exemplo 1: Tema Verde (Padrão)

**Botão Continuar:**
```
Fundo: #385b02
Fundo Hover: #91b381
Borda: #385b02
Texto: #ffffff
```

**Botão Pular:**
```
Fundo: #6c757d
Fundo Hover: #5a6268
Texto: #ffffff
```

---

### Exemplo 2: Tema Azul Corporativo

**Botão Continuar:**
```
Fundo: #0056b3
Fundo Hover: #004494
Borda: #0056b3
Texto: #ffffff
```

**Botão Pular:**
```
Fundo: #6c757d
Fundo Hover: #5a6268
Texto: #ffffff
```

**Botão Google:**
```
Fundo: #ffffff
Fundo Hover: #f8f9fa
Borda: #0056b3
Texto: #0056b3
```

**Botão Facebook:**
```
Fundo: #0056b3
Fundo Hover: #004494
Texto: #ffffff
```

---

### Exemplo 3: Tema Escuro

**Botão Continuar:**
```
Fundo: #212529
Fundo Hover: #343a40
Borda: #495057
Texto: #ffffff
```

**Botão Pular:**
```
Fundo: #495057
Fundo Hover: #6c757d
Texto: #ffffff
```

**Botão Google:**
```
Fundo: #343a40
Fundo Hover: #495057
Borda: #6c757d
Texto: #ffffff
```

**Botão Facebook:**
```
Fundo: #343a40
Fundo Hover: #495057
Borda: #6c757d
Texto: #ffffff
```

---

### Exemplo 4: Tema Minimalista

**Botão Continuar:**
```
Fundo: #000000
Fundo Hover: #333333
Borda: #000000
Texto: #ffffff
```

**Botão Pular:**
```
Fundo: #ffffff
Fundo Hover: #f8f9fa
Borda: #000000
Texto: #000000
```

**Botão Google:**
```
Fundo: #ffffff
Fundo Hover: #f8f9fa
Borda: #e0e0e0
Texto: #000000
```

**Botão Facebook:**
```
Fundo: #ffffff
Fundo Hover: #f8f9fa
Borda: #e0e0e0
Texto: #000000
```

---

## Boas Práticas

### Contraste

- ✅ **Bom**: Fundo escuro + Texto claro
- ✅ **Bom**: Fundo claro + Texto escuro
- ❌ **Ruim**: Fundo claro + Texto claro
- ❌ **Ruim**: Fundo escuro + Texto escuro

### Consistência

- Use cores que combinem com o tema do seu site
- Mantenha hierarquia visual clara:
  - Botão principal (Continuar): Cor mais chamativa
  - Botão secundário (Pular): Cor mais neutra
  - Botões sociais: Cores oficiais ou combinando com o tema

### Acessibilidade

- Garanta contraste mínimo de 4.5:1 entre texto e fundo
- Teste com diferentes tipos de daltonismo
- Use ferramentas como [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)

### Estados Hover

- O hover deve ser visualmente diferente do estado normal
- Geralmente, use uma versão mais escura (fundos escuros) ou mais clara (fundos claros)
- Evite mudanças muito drásticas que podem assustar o usuário

---

## Compatibilidade com Marca

### Google

Se você deseja manter a identidade visual do Google:
```
Fundo: #ffffff (branco oficial do Google)
Fundo Hover: #f8f9fa
Borda: #dadce0
Texto: #3c4043
```

### Facebook

Se você deseja manter a identidade visual do Facebook:
```
Fundo: #1877f2 (azul oficial do Facebook)
Fundo Hover: #166fe5
Texto: #ffffff
```

---

## Troubleshooting

### As cores não estão sendo aplicadas

**Possíveis causas:**

1. **Cache do navegador**
   - Solução: Limpe o cache (Ctrl+Shift+Del)
   
2. **Cache do site**
   - Solução: Limpe cache do plugin de cache (WP Rocket, W3 Total Cache, etc.)
   
3. **CSS personalizado sobrescrevendo**
   - Solução: Verifique se há CSS customizado no tema que use `!important`
   
4. **Não salvou as configurações**
   - Solução: Certifique-se de clicar em "Salvar Configurações"

### As cores aparecem diferentes do esperado

**Possíveis causas:**

1. **Código HEX incorreto**
   - Solução: Verifique se o código está no formato correto (#RRGGBB)
   
2. **Transparência**
   - Solução: O plugin aceita apenas cores sólidas (HEX), não rgba
   
3. **Conflito com tema**
   - Solução: Use o inspetor do navegador (F12) para verificar qual CSS está sendo aplicado

### O botão ficou invisível

**Causa provável:** Fundo e texto da mesma cor

**Solução:**
1. Escolha cores contrastantes
2. Se não lembrar as cores, restaure os padrões:
   - Apague o valor do campo
   - Salve
   - Os padrões serão restaurados automaticamente

---

## CSS Personalizado Avançado

Se você precisa de customizações ainda mais específicas, pode adicionar CSS personalizado:

### Em Appearance → Customize → Additional CSS:

```css
/* Adicionar sombra ao botão continuar */
#llrp-email-submit {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
}

/* Bordas arredondadas nos botões sociais */
.llrp-google-button,
.llrp-facebook-button {
  border-radius: 8px !important;
}

/* Botão pular com borda pontilhada */
#llrp-skip-to-checkout {
  border-style: dashed !important;
}

/* Animação suave no hover */
.llrp-step button {
  transition: all 0.3s ease !important;
}
```

---

## Changelog

### v1.3.1
- ✅ Adicionado controle individual de cores para cada botão
- ✅ 5 tipos de botões customizáveis
- ✅ 6 opções de cor por botão (30 opções no total)
- ✅ Cores padrão alinhadas com diretrizes de marca (Google, Facebook)
- ✅ Removido CSS fixo que impedia customização
- ✅ Mantida compatibilidade com configurações anteriores

---

## FAQ

**P: Posso usar cores gradiente?**
R: Não diretamente no admin. Use CSS personalizado para gradientes.

**P: As cores afetam botões fora do popup?**
R: Não, apenas os botões dentro do popup de login são afetados.

**P: Posso ter cores diferentes por página?**
R: Não nativamente. Seria necessário CSS personalizado com seletores específicos.

**P: O que acontece se eu deixar um campo vazio?**
R: A cor padrão será usada automaticamente.

**P: Posso copiar configurações de cores entre sites?**
R: Sim, exporte as configurações do plugin e importe no outro site.

**P: Há limite de cores que posso usar?**
R: Não, qualquer cor HEX válida é aceita.

---

**Desenvolvido por**: David William da Costa  
**Versão**: 1.3.1  
**Última Atualização**: Janeiro 2026
