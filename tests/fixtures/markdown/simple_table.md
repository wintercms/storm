header 1 | header 2
-------- | --------
cell 1.1 | cell 1.2
cell 2.1 | cell 2.2

---

mismatched 1 | mismatched 2 | mismatched 3
-------- | --------
cell 1.1 | cell 1.2
cell 2.1 | cell 2.2

---

header 1 | header 2
:------- | --------
cell 1.1 | cell 1.2
cell 2.1 | cell 2.2

---

Not a table.
header 1
:-------
cell 1.1
cell 2.1

---

Not a table.
header 1
-------|
cell 1.1
cell 2.1

---

Is a table.
| header 1 |
| -------- |
| cell 1.1 |
| cell 2.1 |

---

Tables can immediately follow a paragraph.
header 1 | header 2
-------- | --------
cell 1.1 | cell 1.2
cell 2.1 | cell 2.2
